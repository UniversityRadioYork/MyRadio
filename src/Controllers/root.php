<?php

/**
 * This is the Root Controller - it is the backbone of everything MyRadio
 *
 * @package MyRadio_Core
 */

use \MyRadio\Config;
use \MyRadio\MyRadioError;
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
