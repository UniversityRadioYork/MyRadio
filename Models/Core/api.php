<?php
/**
 * This is the magical file that provides access to URY's backend data services
 * 
 * @todo This should abstract above Rapier, not directly to backend services
 * @version 22052012
 * @author Lloyd Wallis <lpw@ury.york.ac.uk> 
 */

//Create a function to autoload classes when needed
spl_autoload_register(function($class){
  $class .= '.php';
  if (file_exists(__DIR__.'/../../Classes/ServiceAPI/'.$class)) {
    require_once 'Classes/ServiceAPI/'.$class;
  }
});
set_exception_handler(function($e){});
require_once 'Classes/Config.php';
require_once 'Classes/MyURYException.php';

//Initiate Database
require_once 'Classes/Database.php';

//Initiate Cache
require_once 'Interfaces/CacheProvider.php';
require_once 'Classes/'.Config::$cache_provider.'.php';