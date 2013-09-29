<?php

/*
 * This file provides the SIS_Utils class for MyURY
 * @package MyURY_SIS
 */

/**
 * This class has helper functions for building SIS
 * 
 * @version 20130926
 * @author Andy Durant <aj@ury.org.uk>
 * @package MyURY_SIS
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
				array_push($loadedModules, $moduleInfo);
			}
			return $loadedModules;
		}
		return false;
	}

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
				array_push($loadedModules, $module);
			}
			return $modules;
		}
		return false;
	}

	public static function getPlugins() {
		return self::getModulesForUser(Config::$sis_plugin_folder);
	}

	public static function getTabs() {
		return self::getModulesForUser(Config::$sis_tab_folder);
	}
}