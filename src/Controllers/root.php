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

if (stream_resolve_include_path('MyRadio_Config.local.php')) {
  require_once 'MyRadio_Config.local.php';
  if (Config::$setup === true) {
    require 'Controllers/Setup/root.php';
    exit;
  }
} else {
  /**
   * This install hasn't been configured yet. We should do that.
   */
  require 'Controllers/Setup/root.php';
  exit;

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


<<<<<<< HEAD
set_error_handler('\MyRadio\MyRadioError::errorsToEmail');

//Prepare ServiceAPI's Database and Cache connections
ServiceAPI::wakeup();
=======
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
>>>>>>> 9f0eff6... Added progress so far so someone can review it, if they want.

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
