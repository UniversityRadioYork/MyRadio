<?php
/**
 * Sets up the parts of the MyRadio API that should not trigger a database
 * connection as part of being initialised. This is used to enable the early
 * stages of the setup tools.
 *
 * @version 20140501
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @package MyRadio_Core
 */

/**
 * This number is incremented every time a database patch is released.
 * Patches are scripts in schema/patches.
 */
define('MYRADIO_CURRENT_SCHEMA_VERSION', 0);

require_once 'Interfaces/Singleton.php';
//Create a function to autoload classes when needed
spl_autoload_register(function ($class) {
    $class .= '.php';
    if (stream_resolve_include_path('Classes/ServiceAPI/' . $class)) {
        //This path *must* be absolute - differing versions causes it to be reincluded otherwise
        require_once __DIR__ . '/../../Interfaces/MyRadio_DataSource.php';
        require_once __DIR__ . '/../../Interfaces/IServiceAPI.php';
        require_once 'Classes/ServiceAPI/'. $class;

        return;
    }

    /**
     * @todo Is there a better way of doing this?
     */
    foreach (array('MyRadio', 'NIPSWeb', 'SIS', 'iTones', 'vendor', 'BRA') as $dir) {
        if (stream_resolve_include_path('Classes/' . $dir . '/' . $class)) {
            require_once 'Classes/'. $dir . '/' . $class;
            return;
        }
    }
});

require_once 'Classes/MyRadioException.php';