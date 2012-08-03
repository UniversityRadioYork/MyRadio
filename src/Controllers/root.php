<?php

/**
 * This is the Root Controller - it is the backbone of every request, preparing resources and passing the request onto
 * the necessary handler.
 * 
 * Some clarification about the two different 'Core' parts. They both use the package MyURY_Core, but the folders
 * distinguish them. Items iin folders such as Models/Core or just Controllers are part of the Core code required
 * to make any of the MyURY system work, including Service access.
 * 
 * Items in Models/MyURY/Core etc. are required to make the MyURY Service work - its module/action system. The
 * Services system should be able to operate without this, but not other modules. I hope this clears things up a bit.
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 21072012
 * @package MyURY_Core
 * @uses Shibbobleh
 * @uses \CacheProvider
 * @uses \Database
 * @uses \CoreUtils
 */
/**
 * Turn on Error Reporting for the base start. Once the Config object is loaded this is altered based on the
 * Config::$debug setting
 */
error_reporting(E_ALL ^ E_STRICT);
ini_set('display_errors', 'On');
/**
 * Set the TimeZone
 */
date_default_timezone_get('Europe/London');
/**
 * Sets the include path to include MyURY at the end - makes for nicer includes
 */
ini_set('include_path', str_replace('Controllers', '', __DIR__) . ':' . ini_get('include_path'));

/**
 * The Shibbobleh Client checks whether the user is authenticated, asking them to login via its captive portal
 * if necessary. See the Shibbobleh project documentation for more information about what it provides.
 */
require_once 'shibbobleh_client.php';
/**
 * The CoreUtils static class provides some useful standard functions for MyURY. Take a look at it before you start
 * developing - it may just save you some head scratching.
 */
require_once 'Classes/MyURY/CoreUtils.php';
/**
 * Load up the general Configurables - this includes things like the Database connection settings, the CacheProvider
 * to use and whether debug mode is enabled.
 */
require_once 'Classes/Config.php';

/**
 * Turn off visible error reporting, if needed
 * 269 is AUTH_SHOWERRORS - the constants aren't initialised yet
 */
if (!Config::$display_errors && !CoreUtils::hasPermission(269)) {
  ini_set('display_errors', 'Off');
}

/**
 * Load the phpError handler class
 * and set the error handler
 */
require_once 'Classes/MyURYError.php';
set_error_handler('MyURYError::errorsToArray');

/**
 * Call the model that prepares the Database and the Global Abstraction API 
 */
require 'Models/Core/api.php';

/**
 * Set up the Module and Action global variables. These are used by Module/Action controllers as well as this file.
 * Notice how the default Module is Core. This is basically the MyURY Menu, and maybe a couple of admin pages.
 * Notice how the default Action is 'default'. This means that the "default" Controller should exist for all Modules.
 * Notice how the default Service is MyURY. This means that by default MyURY will be used.
 */
$module = (isset($_REQUEST['module']) ? $_REQUEST['module'] : 'Core');
$action = (isset($_REQUEST['action']) ? $_REQUEST['action'] : 'default');
$service = (isset($_REQUEST['service']) ? $_REQUEST['service'] : 'MyURY');

/**
 * The Service Broker decides what version of a Service the user has access to. This includes MyURY, so gets added
 * here.
 * @todo Discuss or document the parts of MyURY core that cannot be brokered, see if this can be moved earlier
 */
require_once 'Controllers/service_broker.php';

/**
 * If it is a MyURY request, check it exists first
 * a 404 is better than a random 403 for no reason.
 */
if ($service === 'MyURY' && !CoreUtils::isValidController($module, $action)) {
  //Yep, that doesn't exist.
  require 'Controllers/Errors/404.php';
  exit;
}
/**
 * Use the Database authentication data to check whether the use has permission to access that.
 * This method will automatically cause a premature exit if necessary.
 * 
 * IMPORTANT: This will cause a fatal error if an action does not have any permissions associated with it.
 * This is to prevent developers from forgetting to assign permissions to an action.
 */
CoreUtils::requirePermissionAuto($service, $module, $action);

/**
 * Include the Global Bootstrap for the Service - This is just sets up another autoloader and possibly
 * some more variables. Just take a look at it to see more.
 */
require 'Controllers/'.$service.'/bootstrap.php';
//Include the requested action
require 'Controllers/'.$service.'/' . $module . '/' . $action . '.php';