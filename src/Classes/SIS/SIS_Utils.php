<?php

/*
 * This file provides the SIS_Utils class for MyRadio
 * @package MyRadio_SIS
 */

namespace MyRadio\SIS;

use MyRadio\Config;
use MyRadio\MyRadio\AuthUtils;
use MyRadio\ServiceAPI\ServiceAPI;

/**
 * This class has helper functions for building SIS.
 */
class SIS_Utils extends ServiceAPI
{
    /**
     * Gets the module data (tabs or plugins) based on the active users permissions.
     *
     * @return array modules
     */
    public static function getModulesForUser()
    {
        $modules = Config::$sis_modules;
        $loadedModules = [];
        if ($modules !== false) {
            foreach ($modules as $module) {
                $file = 'Models/SIS/modules/'.$module.'.php';
                if (stream_resolve_include_path($file)) {
                    include $file;
                }
                if (!isset($moduleInfo['required_permission'])
                    || AuthUtils::hasPermission($moduleInfo['required_permission'])
                ) {
                    $loadedModules[] = $module;
                }
            }

            return $loadedModules;
        }

        return false;
    }

    /**
     * Looks up IP location from Campus Network Data or GeoIP.
     *
     * @param string $ip IP address to lookup
     *
     * @return string Location
     */
    public static function ipLookup($ip)
    {
        $query = self::$db->query(
            'SELECT iscollege, description FROM l_subnet WHERE subnet >> $1 ORDER BY description ASC',
            [$ip]
        );

        $location = [];

        if (($query === null) or (pg_num_rows($query) == 0)) {
            $geoip = geoip_record_by_name($ip);
            if ($geoip === false) {
                $location[0] = 'Unknown';
            } elseif (empty($geoip['city'])) {
                $location[0] = "{$geoip['country_name']}";
            } else {
                $location[0] = utf8_encode($geoip['city']) . ", " . "{$geoip['country_name']}";
            }

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
     * Read the loaded modules and returns the poll functions, if configured.
     *
     * @param array $modules the loaded modules
     *
     * @return array $pollFuncs functions to run for LongPolling
     */
    public static function readPolls($modules)
    {
        if ($modules !== false) {
            $pollFuncs = [];
            foreach ($modules as $module) {
                if (stream_resolve_include_path('Models/SIS/modules/'.$module.'.php')) {
                    require 'Models/SIS/modules/'.$module.'.php';
                    if (isset($moduleInfo['pollfunc'])) {
                        $pollFuncs[] = $moduleInfo['pollfunc'];
                    }
                }
            }

            return $pollFuncs;
        }

        return false;
    }

    /**
     * Checks message for suspected spam strings.
     *
     * @param string $message text to test for spam
     *
     * @return bool spam true, else false
     */
    public static function checkMessageSpam($message)
    {
        if (strlen($message) > 1000) {
            return true;
        }
        if (!empty(Config::$spam)) {
            foreach (Config::$spam as $needle) {
                if (stripos($message, $needle) !== false) {
                    return true;
                }
            }
            return false;
        } else {
            return false;
        }
    }

    /**
     * Checks message for suspected social engineering attack.
     *
     * @param string $message text to test for social engineering
     *
     * @return mixed warning string or false
     */
    public static function checkMessageSocialEngineering($message)
    {
        if (!empty(Config::$social_engineering_trigger)) {
            foreach (Config::$social_engineering_trigger as $trigger) {
                if (stripos($message, $trigger) !== false) {
                    return Config::$social_engineering_warning;
                } else {
                    return false;
                }
            }
        } else {
            return false;
        }
    }
}
