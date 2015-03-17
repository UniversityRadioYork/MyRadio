<?php

/*
 * This file provides the SIS_Utils class for MyRadio
 * @package MyRadio_SIS
 */

namespace MyRadio\SIS;

use \MyRadio\Config;
use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\ServiceAPI\ServiceAPI;

/**
 * This class has helper functions for building SIS
 *
 * @package MyRadio_SIS
 */
class SIS_Utils extends ServiceAPI
{
    /**
     * Gets the module data (tabs or plugins) based on the active users permissions
     * @return Array  modules
     */
    public static function getModulesForUser()
    {
        $modules = self::$container['config']->sis_modules;
        $loadedModules = [];
        if ($modules !== false) {
            foreach ($modules as $module) {
                $file = 'Models/SIS/modules/' . $module . '.php';
                if (stream_resolve_include_path($file)) {
                    include $file;
                }
                if (!isset($moduleInfo['required_permission'])
                    || CoreUtils::hasPermission($moduleInfo['required_permission'])
                ) {
                    $loadedModules[] = $module;
                }
            }
            return $loadedModules;
        }
        return false;
    }

    /**
     * Looks up IP location from Campus Network Data or GeoIP
     * @param  String $ip IP address to lookup
     * @return String Location
     */
    public static function ipLookup($ip)
    {
        $query = self::$container['database']->query('SELECT iscollege, description FROM l_subnet WHERE subnet >> $1 ORDER BY description ASC', [$ip]);

        $location = [];

        if (($query === null) or (pg_num_rows($query) == 0)) {
            $geoip = geoip_record_by_name($ip);
            $location[0] = ($geoip === false) ? 'Unknown' : empty($geoip['city']) ? "{$geoip['country_name']}" : utf8_encode($geoip['city']).", {$geoip['country_name']}";

            return $location;
        }
        $q = self::$container['database']->fetchAll($query);
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
                if (stream_resolve_include_path('Models/SIS/modules/' . $module . '.php')) {
                    require 'Models/SIS/modules/' . $module . '.php';
                    if (isset($moduleInfo['pollfunc'])) {
                        $pollFuncs[] = $moduleInfo['pollfunc'];
                    }
                }
            }

            return $pollFuncs;
        }

        return false;
    }
}
