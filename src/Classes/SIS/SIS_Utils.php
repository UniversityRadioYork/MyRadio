<?php

/*
 * This file provides the SIS_Utils class for MyRadio
 * @package MyRadio_SIS
 */

namespace MyRadio\SIS;

use \MyRadio\Config;
use \MyRadio\MyRadio\AuthUtils;
use \MyRadio\ServiceAPI\ServiceAPI;

/**
 * This class has helper functions for building SIS
 *
 * @version 20130926
 * @author Andy Durant <aj@ury.org.uk>
 * @package MyRadio_SIS
 */
class SIS_Utils extends ServiceAPI
{
    /**
     * Creates a list of files from a given directory with an optional filename
     * @param  String $d Path to directory
     * @param  String $x File Extension (optional)
     * @return Array  List of files
     */
    private static function fileList($d, $x)
    {
        return array_diff(scandir(__DIR__.'/../../'.$d), ['.', '..']);
    }

    /**
     * Checks whether the client IP is a machine authorised for full control
     * @param  String      $ip The IP address to check. If null, will use the REMOTE_ADDR server property
     * @return boolean|int The studio's ID number or false if unauthorised
     */
    private static function isAuthenticatedMachine($ip = null)
    {
        if (is_null($ip)) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        foreach (Config::$studios as $key => $studio) {
            if (in_array($ip, $studio['authenticated_machines'])) {
                //This client is authorised
                return $key;
            }
        }

        return false;
    }

    /**
     * Gets module data (tabs or plugins) that are enabled
     * @param  String $moduleFolder The folder to read the modules from
     * @return Array  moduleInfo
     */
    private static function getModules($moduleFolder)
    {
        $modules = self::fileList($moduleFolder, 'php');
        $loadedModules = [];
        if ($modules !== false) {
            foreach ($modules as $key => $module) {
                include $moduleFolder.'/'.$module;
                if (!isset($moduleInfo)) {
                    trigger_error('Error with $module: \$moduleInfo must be set for each module.');
                    continue;
                }
                if (isset($moduleInfo['enabled']) && ($moduleInfo['enabled'] != true)) {
                    continue;
                }
                $loadedModules[] = $moduleInfo;
            }

            return $loadedModules;
        }

        return false;
    }

    /**
     * Gets the module data (tabs or plugins) based on the active users permissions
     * @param  String $moduleFolder The folder to read the modules from
     * @return Array  moduleInfo
     */
    private static function getModulesForUser($moduleFolder)
    {
        $modules = self::getModules($moduleFolder);
        $loadedModules = [];
        if ($modules !== false) {
            foreach ($modules as $key => $module) {
                $notAuth = (isset($module['required_permission']) && !AuthUtils::hasPermission($module['required_permission']));
                /**
                 * @todo Replace with MyRadio built in location Auth
                 */
                $notStudio = (isset($module['required_location']) && ($module['required_location'] === true && self::isAuthenticatedMachine() === false));

                if ($notAuth && $notStudio) {
                    continue;
                }
                $loadedModules[] = $module;
            }

            return $loadedModules;
        }

        return false;
    }

    /**
     * Gets the plugin data from the configured sis_plugin_folder
     * @return Array pluginInfo
     */
    public static function getPlugins()
    {
        return self::getModulesForUser(Config::$sis_plugin_folder);
    }

    /**
     * Gets the tab data from the configured sis_tab_folder
     * @return Array tabInfo
     */
    public static function getTabs()
    {
        return self::getModulesForUser(Config::$sis_tab_folder);
    }

    /**
     * Looks up IP location from Campus Network Data or GeoIP
     * @param  String $ip IP address to lookup
     * @return String Location
     */
    public static function ipLookup($ip)
    {
        $query = self::$db->query('SELECT iscollege, description FROM l_subnet WHERE subnet >> $1 ORDER BY description ASC', [$ip]);

        $location = [];

        if (($query === null) or (pg_num_rows($query) == 0)) {
            $geoip = geoip_record_by_name($ip);
            $location[0] = ($geoip === false) ? 'Unknown' : empty($geoip['city']) ? "{$geoip['country_name']}" : utf8_encode($geoip['city']).", {$geoip['country_name']}";

            return $location;
        }
        $q = self::$db->fetchAll($query);
        foreach ($q as $k) {
            $location[] = $k['description'];
            $location[] = ($k['iscollege'] == 't') ? 'College Bedroom' : 'Study Room / Labs / Wifi';
        }

        return $location;
    }

    /**
     * Read the loaded modules and returns the poll functions, if configured
     * @param  array $modules the loaded modules
     * @return array $pollFuncs functions to run for LongPolling
     */
    public static function readPolls($modules)
    {
        if ($modules !== false) {
            $pollFuncs = [];
            foreach ($modules as $module) {
                if (isset($module['pollfunc'])) {
                    $pollFuncs[] = $module['pollfunc'];
                }
            }

            return $pollFuncs;
        }

        return false;
    }

    /**
     * Check whether to load the Getting Started tab
     * @return boolean
     */
    public static function getShowHelpTab($memberid)
    {
        $result = self::$db->fetchColumn(
            'SELECT helptab FROM sis2.member_options WHERE memberid=$1 LIMIT 1',
            [$memberid]
        );

        if (empty($result)) {
            self::setHelpTab($memberid);
            return true;
        }

        return ($result[0] === 't');
    }

    /**
     * Prevent showing the getting started tab at startup
     */
    public static function hideHelpTab($memberid)
    {
        $result = self::$db->query(
            'UPDATE sis2.member_options SET helptab=false WHERE memberid=$1',
            [$memberid]
        );
    }

    private static function setHelpTab($memberid)
    {
        self::$db->query(
            'INSERT INTO sis2.member_options (memberid, helptab) VALUES ($1, false)',
            [$memberid]
        );
    }
}
