<?php

/**
 * This is the magical file that provides access to URY's backend data services
 * 
 * This file loads up the crtical Interfaces and Classes for MyURY<br>
 * - The Singleton for efficiency, as almost every class uses it<br>
 * - The IServiceAPI (including the ServiceAPI abstract class) to provide a standard way of establishing Database and
 * CacheProvider connections<br>
 * - The MyURY Configuration Class - This file contains the settings required to make MyURY Start<br>
 * - The MyURYException Class - This is used for critical MyURY errors, formatting and communicating them as needed.
 * It also sets the global exception handler as something that does nothing - preventing unwanted output<br>
 * - The Database and CacheProvider Classes/Interfaces to provide access to the MyURY Data Stores<br>
 * - The MyURY ServiceAPI Autoloader dynamically loads additional classes as needed
 * 
 * This file also does the following:<br>
 * - Provides the <code>$member</code> global variable - this contains the current User<br>
 * - Calls CoreUtils::setUpAuth, which configures the MyURY authentication constants
 * 
 * @version 20130515
 * @author Lloyd Wallis <lpw@ury.org.uk> 
 * @package MyURY_Core
 */
require_once 'Interfaces/Singleton.php';
//Create a function to autoload classes when needed
spl_autoload_register(function($class) {
          $class .= '.php';
          if (stream_resolve_include_path('Classes/ServiceAPI/' . $class)) {
            //This path *must* be absolute - differing versions causes it to be reincluded otherwise
            require_once __DIR__ . '/../../Interfaces/MyURY_DataSource.php';
            require_once __DIR__ . '/../../Interfaces/IServiceAPI.php';
            require_once 'Classes/ServiceAPI/' . $class;
            return;
          }
          
          /**
           * @todo Is there a better way of doing this?
           */
          foreach (array('MyURY','NIPSWeb','SIS','iTones','Vendor','BRA') as $dir) {
            if (stream_resolve_include_path('Classes/'.$dir.'/' . $class)) {
              require_once 'Classes/'.$dir.'/' . $class;
              return;
            }
          }
        });

require_once 'Classes/MyURYException.php';
require_once 'Classes/MyURYError.php';
set_exception_handler(function($e) {
          
        });
set_error_handler('MyURYError::errorsToEmail');
register_shutdown_function('CoreUtils::shutdown');

//Initiate Database
require_once 'Classes/Database.php';

//Initiate Cache
require_once 'Interfaces/CacheProvider.php';
require_once 'Classes/' . Config::$cache_provider . '.php';

//Initialise the permission constants
CoreUtils::setUpAuth();