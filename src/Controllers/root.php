<?php
/**
 * 
 * @todo Proper Documentation
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 21072012
 * @package MyURY_Core
 */

//Set up the key environment settings
error_reporting(E_ALL ^ E_STRICT);
ini_set('display_errors', 'On');
date_default_timezone_get('Europe/London');
ini_set('include_path', str_replace('Controllers','',__DIR__).':'.ini_get('include_path'));

/**
 * Yay authentication! 
 */
require_once 'shibbobleh_client.php';
require_once 'Classes/MyURY/CoreUtils.php';

/**
 * Call the model that prepares the database and the Global Abstraction API 
 */
require 'Models/Core/api.php';

$module = (isset($_REQUEST['module']) ? $_REQUEST['module'] : 'Core');
$action = (isset($_REQUEST['action']) ? $_REQUEST['action'] : 'default');


//Find out what service and controller is being requested
if (isset($_REQUEST['service'])) {
  //Oooh... This isn't directly managed by MyURY. Send it to the Service Broker
  require 'Controllers/service_broker.php';
}
/*
 * The default service is requested - that's MyURY!
 * Check if the module/action combination is valid
 */
elseif (CoreUtils::isValidController($module, $action)) {
  //Include the bootstrap
  require 'Controllers/MyURY/bootstrap.php';
  //Include the requested action
  require 'Controllers/MyURY/'.$module.'/'.$action.'.php';
} else {
  //That doesn't seem to be any useful information
  require 'Controllers/Errors/404.php';
}