<?php

/**
 * This is the Root Controller - it is the backbone of everything MyRadio
 *
 * @package MyRadio_Core
 */

use \MyRadio\Config;
use \MyRadio\MyRadioError;
use \MyRadio\ServiceAPI\ServiceAPI;
use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\MyRadio\MyRadioSession;
use \MyRadio\MyRadio\MyRadioNullSession;

/**
 * Turn on Error Reporting for the start. Once the Config object is loaded
 * this is updated to reflect Config.
 */
error_reporting(E_ALL);
ini_set('display_errors', 'On');
/**
 * Set the Default Timezone.
 * Once Config is available, this value should be used instead.
 */
date_default_timezone_set('Europe/London');
/**
 * Sets the include path to include MyRadio at the end - makes for nicer includes
 */
set_include_path(str_replace('Controllers', '', __DIR__) . PATH_SEPARATOR . get_include_path());
/**
 * Sets up the autoloader for all classes
 */
require_once 'Classes/Autoloader.php';
// instantiate the loader
$loader = new \MyRadio\Autoloader;
// register the autoloader
$loader->register();
// register the base directories for the namespace prefix
$_basepath = str_replace('Controllers', '', __DIR__) . DIRECTORY_SEPARATOR;

$loader->addNamespace('MyRadio', $_basepath . 'Classes');
$loader->addNamespace('MyRadio\Iface', $_basepath . 'Interfaces');

/**
 * Load configuration specific to this system.
 */
require 'MyRadio_Config.local.php';

/**
 * Set local timezone.
 */
date_default_timezone_set(Config::$timezone);


set_error_handler('\MyRadio\MyRadioError::errorsToEmail');

/**
 * Turn off visible error reporting, if needed
 * 269 is AUTH_SHOWERRORS - the constants aren't initialised yet
 */
if (!Config::$display_errors && !CoreUtils::hasPermission(269)) {
    ini_set('display_errors', 'Off');
}
ini_set('error_log', Config::$log_file); // Set error log file
date_default_timezone_set(Config::$timezone); //Set timezone

/**
 * Set up the Module and Action global variables. These are used by Module/Action controllers as well as this file.
 * Notice how the default Module is MyRadio. This is basically the MyRadio Menu, and maybe a couple of admin pages.
 * Notice how the default Action is 'default'. This means that the "default" Controller should exist for all Modules.
 * The top half deals with Rewritten URLs, which get mapped to ?request=
 */
if (isset($_REQUEST['request'])) {
    $info = explode('/', $_REQUEST['request']);
    //If both are defined, it's Module/Action
    if (!empty($info[1])) {
        $module = $info[0];
        $action = $info[1];
        //If there's only one, determine if it's the module or action
    } elseif (CoreUtils::isValidController(Config::$default_module, $info[0])) {
        $module = Config::$default_module;
        $action = $info[0];
    } elseif (CoreUtils::isValidController($info[0], Config::$default_action)) {
        $module = $info[0];
        $action = Config::$default_action;
    } else {
        require 'Controllers/Errors/404.php';
        exit;
    }
} else {
    $module = (isset($_REQUEST['module']) ? $_REQUEST['module'] : Config::$default_module);
    $action = (isset($_REQUEST['action']) ? $_REQUEST['action'] : Config::$default_action);
    if (!CoreUtils::isValidController($module, $action)) {
        //Yep, that doesn't exist.
        require 'Controllers/Errors/404.php';
        exit;
    }
}

/**
 * Use the Database authentication data to check whether the use has permission to access that.
 * This method will automatically cause a premature exit if necessary.
 *
 * IMPORTANT: This will cause a fatal error if an action does not have any permissions associated with it.
 * This is to prevent developers from forgetting to assign permissions to an action.
 */
CoreUtils::requirePermissionAuto($module, $action);

/**
 * If a Joyride is defined, start it
 */
if (isset($_REQUEST['joyride'])) {
    $_SESSION['joyride'] = $_REQUEST['joyride'];
}

//Wake up ServiceAPI if it isn't already
//Otherwise ServiceAPI::$db/$cache may not be available and upset controllers
ServiceAPI::wakeup();

//Initialise the permission constants
CoreUtils::setUpAuth();

//Set up a shutdown function
//AFTER other things to ensure DB is registered
register_shutdown_function('\MyRadio\MyRadio\CoreUtils::shutdown');

/**
 * Sets up a session stored in the database - uesful for sharing between more
 * than one server.
 * We disable this for the API using the DISABLE_SESSION constant.
 */
//Override any existing session
if (isset($_SESSION)) {
    session_write_close();
    session_id($_COOKIE['PHPSESSID']);
}

if ((!defined('DISABLE_SESSION')) or DISABLE_SESSION === false) {
    $session_handler = MyRadioSession::factory();
} else {
    $session_handler = MyRadioNullSession::factory();
}

session_set_save_handler(
    [$session_handler, 'open'],
    [$session_handler, 'close'],
    [$session_handler, 'read'],
    [$session_handler, 'write'],
    [$session_handler, 'destroy'],
    [$session_handler, 'gc']
);
session_start();
