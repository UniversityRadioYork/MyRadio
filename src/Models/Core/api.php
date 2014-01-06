<?php

/**
 * This is the magical file that provides access to URY's backend data services
 * 
 * This file loads up the crtical Interfaces and Classes for MyRadio<br>
 * - The Singleton for efficiency, as almost every class uses it<br>
 * - The IServiceAPI (including the ServiceAPI abstract class) to provide a standard way of establishing Database and
 * CacheProvider connections<br>
 * - The MyRadio Configuration Class - This file contains the settings required to make MyRadio Start<br>
 * - The MyRadioException Class - This is used for critical MyRadio errors, formatting and communicating them as needed.
 * It also sets the global exception handler as something that does nothing - preventing unwanted output<br>
 * - The Database and CacheProvider Classes/Interfaces to provide access to the MyRadio Data Stores<br>
 * - The MyRadio ServiceAPI Autoloader dynamically loads additional classes as needed
 * 
 * This file also does the following:<br>
 * - Provides the <code>$member</code> global variable - this contains the current User<br>
 * - Calls CoreUtils::setUpAuth, which configures the MyRadio authentication constants
 * 
 * @version 20130515
 * @author Lloyd Wallis <lpw@ury.org.uk> 
 * @package MyRadio_Core
 */
require_once 'Interfaces/Singleton.php';
//Create a function to autoload classes when needed
spl_autoload_register(function($class) {
            $class .= '.php';
            if (stream_resolve_include_path('Classes/ServiceAPI/' . $class)) {
                //This path *must* be absolute - differing versions causes it to be reincluded otherwise
                require_once __DIR__ . '/../../Interfaces/MyRadio_DataSource.php';
                require_once __DIR__ . '/../../Interfaces/IServiceAPI.php';
                require_once 'Classes/ServiceAPI/' . $class;
                return;
            }

            /**
             * @todo Is there a better way of doing this?
             */
            foreach (array('MyRadio', 'NIPSWeb', 'SIS', 'iTones', 'Vendor', 'BRA') as $dir) {
                if (stream_resolve_include_path('Classes/' . $dir . '/' . $class)) {
                    require_once 'Classes/' . $dir . '/' . $class;
                    return;
                }
            }
        });

require_once 'Classes/MyRadioException.php';
require_once 'Classes/MyRadioError.php';
set_exception_handler(function($e) {
            
        });
set_error_handler('MyRadioError::errorsToEmail');

//Initiate Database
require_once 'Classes/Database.php';

//Initiate Cache
require_once 'Interfaces/CacheProvider.php';
require_once 'Classes/' . Config::$cache_provider . '.php';

//Initialise the permission constants
CoreUtils::setUpAuth();

//Set up a shutdown function
//AFTER other things to ensure DB is registered
register_shutdown_function('CoreUtils::shutdown');

/**
 * Sets up a session stored in the database - uesful for sharing between more
 * than one server.
 * We disable this for the API using the DISABLE_SESSION constant.
 */
if ((!defined('DISABLE_SESSION')) or DISABLE_SESSION === false) {
    $session_handler = MyRadioSession::factory();
    session_set_save_handler(
            array($session_handler, 'open'),
            array($session_handler, 'close'),
            array($session_handler, 'read'),
            array($session_handler, 'write'),
            array($session_handler, 'destroy'),
            array($session_handler, 'gc')
    );
    session_start();
}