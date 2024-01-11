<?php

/**
 * This is the Root Controller - it is the backbone of everything MyRadio.
 */
use \MyRadio\Config;
use \MyRadio\ServiceAPI\ServiceAPI;
use \MyRadio\MyRadio\AuthUtils;
use \MyRadio\MyRadio\MyRadioSession;
use \MyRadio\MyRadio\MyRadioNullSession;

/*
 * This number is incremented every time a database patch is released.
 * Patches are scripts in schema/patches.
 */
define('MYRADIO_CURRENT_SCHEMA_VERSION', 15);

/*
 * Turn on Error Reporting for the start. Once the Config object is loaded
 * this is updated to reflect Config.
 */
error_reporting(E_ALL);
ini_set('display_errors', 'On');
/*
 * Set the Default Timezone.
 * Once Config is available, this value should be used instead.
 */
date_default_timezone_set('Europe/London');
/*
 * Sets the include path to include MyRadio at the end - makes for nicer includes
 */
set_include_path(str_replace('Controllers', '', __DIR__).PATH_SEPARATOR.get_include_path());

/**
 * Sets up the autoloader for all MyRadio classes.
 */
require_once 'Classes/Autoloader.php';
// instantiate the loader
$loader = new \MyRadio\Autoloader();
// register the autoloader
$loader->register();
// register the base directories for the namespace prefix
$_basepath = str_replace('Controllers', '', __DIR__).DIRECTORY_SEPARATOR;

$loader->addNamespace('MyRadio', $_basepath.'Classes');
$loader->addNamespace('MyRadio\Iface', $_basepath.'Interfaces');

unset($_basepath);

/**
 * Sets up the autoloader for composer.
 */
require 'vendor/autoload.php';

/*
 * Load configuration specific to this system.
 * Or, if it doesn't exist, kick into setup.
 */
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
}

set_error_handler('\MyRadio\MyRadioError::errorsToArray');
set_exception_handler(
    function ($e) {
        if (method_exists($e, 'uncaught')) {
            $e->uncaught();
        } else {
            echo 'This information is not available at the moment. Please try again later.';
            print_r($e);
        }
    }
);

// Set error log file
ini_set('error_log', Config::$log_file);

//Wake up ServiceAPI if it isn't already
//Otherwise ServiceAPI::$db/$cache may not be available and upset controllers
ServiceAPI::wakeup();

//Initialise the permission constants
AuthUtils::setUpAuth();

/*
 * Turn off visible error reporting, if needed
 * must come after AuthUtils::setUpAuth()
 */
if (!Config::$display_errors && !AuthUtils::hasPermission(AUTH_SHOWERRORS)) {
    ini_set('display_errors', 'Off');
}

//Set up a shutdown function
//AFTER other things to ensure DB is registered
register_shutdown_function('\MyRadio\MyRadio\CoreUtils::shutdown');

/*
 * Sets up a session stored in the database - uesful for sharing between more
 * than one server.
 */
//Override any existing session
if (isset($_SESSION)) {
    session_write_close();
    session_id($_COOKIE['PHPSESSID']);
}

if ((!defined('DISABLE_SESSION')) or DISABLE_SESSION === false) {
    $session_handler = MyRadioSession::factory();
    // Changing the serialize handler to the general serialize/unserialize methods lets us
    // read sessions without actually having to activate them and read them into $_SESSION
    ini_set('session.serialize_handler', 'php_serialize');
    ini_set('session.cookie_samesite', 'None');
    ini_set('session.cookie_secure', 'True');
    session_set_save_handler($session_handler, true);
    session_start();
}
