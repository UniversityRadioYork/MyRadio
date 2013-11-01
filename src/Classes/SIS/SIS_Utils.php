<?php

/*
 * This file provides the SIS_Utils class for MyRadio
 * @package MyRadio_SIS
 */

/**
 * This class has helper functions for building SIS
 * 
 * @version 20130926
 * @author Andy Durant <aj@ury.org.uk>
 * @package MyRadio_SIS
 */
class SIS_Utils extends ServiceAPI {

	/**
	 * Creates a list of files from a given directory with an optional filename
	 * @param  String $d Path to directory
	 * @param  String $x File Extension (optional)
	 * @return Array    List of files
	 */
	private static function file_list($d,$x){ 
		return array_diff(scandir(Config::$base_path.'/'.$d),array('.','..'));
	}

	/**
	 * Checks whether the client IP is a machine authorised for full control
	 * @param  String  $ip The IP address to check. If null, will use the REMOTE_ADDR server property
	 * @return boolean|int     The studio's ID number or false if unauthorised
	 */
	private static function isAuthenticatedMachine($ip = null) {
	  if (is_null($ip))
		$ip = $_SERVER['REMOTE_ADDR'];

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
	 * @return Array                moduleInfo
	 */
	private static function getModules($moduleFolder) {
		$modules = self::file_list($moduleFolder,'php');
		$loadedModules = array();
		if ($modules !== false) {
			foreach ($modules as $key => $module) {
				include Config::$base_path.'/'.$moduleFolder.'/'.$module;
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
	 * @return Array                moduleInfo
	 */
	private static function getModulesForUser($moduleFolder) {
		$modules = self::getModules($moduleFolder);
		$loadedModules = array();
		if ($modules !== false) {
			foreach ($modules as $key => $module) {
				if (isset($module['required_permission']) && !CoreUtils::hasPermission($module['required_permission'])) {
					continue;
				}
				if (isset($module['required_location']) && ($module['required_location'] === True && self::isAuthenticatedMachine() === False)) {
					continue;
				}
				$loadedModules[] = $module;
			}
			return $modules;
		}
		return false;
	}

	/**
	 * Gets the plugin data from the configured sis_plugin_folder
	 * @return Array pluginInfo
	 */
	public static function getPlugins() {
		return self::getModulesForUser(Config::$sis_plugin_folder);
	}

	/**
	 * Gets the tab data from the configured sis_tab_folder
	 * @return Array tabInfo
	 */
	public static function getTabs() {
		return self::getModulesForUser(Config::$sis_tab_folder);
	}

	/**
	 * Looks up IP location from Campus Network Data or GeoIP
	 * @param  String $ip IP address to lookup
	 * @return String     Location
	 */
	public static function ipLookup($ip) {
		$query = self::$db->query('SELECT iscollege, description FROM l_subnet WHERE subnet >> $1 ORDER BY description ASC', array($ip));

		$location = array();

		if (($query === null) or (pg_num_rows($query) == 0)) {
			$geoip = geoip_record_by_name($ip);
			$location[0] = ($geoip === FALSE) ? 'Unknown' : isset($geoip['city']) ? "{$geoip['city']}, {$geoip['country_name']}" : "{$geoip['country_name']}";
			return $location;
		}
		$q = self::$db->fetch_all($query);
		foreach ($q as $k) {
			$location[] = $k['description'];
			$location[] = ($k['iscollege'] == 't') ? 'College Bedroom' : 'Study Room / Labs / Wifi';
		}
		return $location;
	}

	/**
	 * Read the loaded modules and returns the poll functions, if configured
	 * @param array $modules the loaded modules
	 * @return array $pollFuncs functions to run for LongPolling
	 */
	public static function readPolls($modules) {
		if ($modules !== false) {
			$pollFuncs = array();
			foreach ($modules as $module) {
				if (isset($module['pollfunc'])) {
					$pollFuncs[] = $module['pollfunc'];
				}
			}
			return $pollFuncs;
		}
		return false;
	}
}