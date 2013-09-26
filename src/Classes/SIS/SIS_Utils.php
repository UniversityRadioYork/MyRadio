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

	private function file_list($d,$x){ 
       foreach(array_diff(scandir($d),array('.','..')) as $f)if(is_file($d.'/'.$f)&&(($x)?ereg($x.'$',$f):1))$l[]=$f; 
       return $l; 
	}

	private function getModules($moduleFolder) {
		$modules = file_list($moduleFolder,'php');
		$loadedModules = array();
		if ($modules !== FALSE) {
			foreach ($modules as $module) {
				include $module;
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
		return 0;
	}

	public static function getPluginsForUser($pluginFolder = Config::$sis_plugin_folder, $user) {
		$plugins = getModules($pluginFolder);
	}

	public static function getTabsForUser($tabFolder = Config::$sis_tab_folder, $user) {
		$tabs = getModules($tabFolder);
	}
}