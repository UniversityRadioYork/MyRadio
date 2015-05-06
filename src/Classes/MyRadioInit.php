<?php

/**
 * This file provides the Init class for MyRadio.
 *
 * MyRadioInit is the set of functions that set up the environment for handling
 * MyRadio requests.
 *
 * @package MyRadio
 */

namespace MyRadio;

use \MyRadio\MyRadioError;
use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\MyRadio\MyRadioSession;
use \MyRadio\MyRadio\MyRadioNullSession;
use \MyRadio\Iface\CacheProvider;

use \Aura\Di\Container;
use \Aura\Di\Factory;

class MyRadioInit
{
    /**
     * Sets up the basic environment - constants and include path
     */
    protected static function setupPreConfigEnvironment()
    {
        /**
         * This number is incremented every time a database patch is released.
         * Patches are scripts in schema/patches.
         */
        if (!defined('MYRADIO_CURRENT_SCHEMA_VERSION')) {
            define('MYRADIO_CURRENT_SCHEMA_VERSION', 0);
        }
        /**
         * Sets the include path to include MyRadio at the end - makes for nicer includes
         */
        set_include_path(str_replace('Classes', '', __DIR__) . PATH_SEPARATOR . get_include_path());

        return true;
    }

    /**
     * MyRadio has classes. Lots of them. And dependencies.
     * Here we set up some handlers so we know where to find them and link them up.
     */
    protected static function setupAutoloaders()
    {
        /**
         * Sets up the autoloader for all MyRadio classes
         */
        require_once 'Classes/Autoloader.php';
        // instantiate the loader
        $loader = new \MyRadio\Autoloader;
        // register the autoloader
        $loader->register();
        // register the base directories for the namespace prefix
        $_basepath = str_replace('Classes', '', __DIR__) . DIRECTORY_SEPARATOR;

        $loader->addNamespace('MyRadio', $_basepath . 'Classes');
        $loader->addNamespace('MyRadio\Iface', $_basepath . 'Interfaces');
        $loader->addNamespace('MyRadio\Traits', $_basepath . 'Traits');

        unset($_basepath);

        /**
         * Sets up the autoloader for composer
         */
        require_once 'vendor/autoload.php';
    }

    /**
     * Build a Container with services. Register it with ServiceAPI.
     * @return Container
     * @codeCoverageIgnore
     */
    protected static function setupServiceContainer()
    {
        $container = new Container(new Factory);

        //Service singletons
        $container->set('config', new Config);
        $container->set('database', new Database);

        // Define implementations of interfaces
        $container->types['CacheProvider'] = $container->lazyNew(
            $container->get('config')->cache_provider
        );
        $container->params['CacheProvider']['enable'] = $container->get('config')->cache_enable;

        $factory = new \MyRadio\MyRadio\MyRadioServiceFactory($container);

        $container->setter['MyRadio\Traits\Configurable']['setConfig'] = $container->lazyGet('config');
        $container->setter['MyRadio\Traits\DatabaseSubject']['setDatabase'] = $container->get('database');
        $container->setter['MyRadio\Traits\ServiceFactorySubject']['setServiceFactory'] = $factory;
        $container->setter['MyRadio\Traits\SessionSubject']['setSession'] = $container->lazyGet('session');

        if ((!defined('DISABLE_SESSION')) or DISABLE_SESSION === false) {
            $session_handler = 'MyRadio\MyRadio\MyRadioSession';
        } else {
            $session_handler = 'MyRadio\MyRadio\MyRadioNullSession';
        }
        $container->set('session', $container->lazyNew($session_handler));

        $container->set('utils', $container->lazyNew('MyRadio\MyRadio\CoreUtils'));

        return $container;
    }

    /**
     * Load in MyRadio_Config.local.php and launch setup if necessary.
     *
     * @return boolean false if setup has been loaded. Calling process should halt execution.
     */
    protected static function loadConfigDatabaseAndCheckForSetup($container)
    {
        /**
         * Load configuration specific to this system.
         * Or, if it doesn't exist, kick into setup.
         */
        if (stream_resolve_include_path('MyRadio_Config.local.php')) {
            $config = $container->get('config');
            require_once 'MyRadio_Config.local.php';

            $container->get('database')->connect($config);

            if ($config->setup === true) {
                require 'Controllers/Setup/root.php';
                return false;
            }
        } else {
            /**
             * This install hasn't been configured yet. We should do that.
             */
            require 'Controllers/Setup/root.php';
            return false;
        }
        return true;
    }

    /**
     * Authentication contants, Error and exception handling
     */
    protected static function setupAuthErrorAndExceptionHandlers($container)
    {
        set_error_handler('MyRadio\MyRadioError::errorsToArray');
        set_exception_handler(
            function ($e) use ($container) {
                if (method_exists($e, 'uncaught')) {
                    $e->uncaught($container);
                } else {
                    var_dump($e);
                    echo 'This information is not available at the moment. Please try again later.';
                }
            }
        );

        // Set error log file
        ini_set('error_log', $container->get('config')->log_file);

        //Initialise the permission constants
        $container->get('utils')->setUpAuth();

        /**
         * Turn off visible error reporting, if needed
         * must come after CoreUtils::setUpAuth()
         */
        if (!$container->get('config')->display_errors && !$container->get('utils')->hasPermission(AUTH_SHOWERRORS)) {
            //ini_set('display_errors', 'Off');
        }
    }

    /**
     * Register shutdown functions
     */
    protected static function setupTearDown($container)
    {
        register_shutdown_function(function() use ($container) {
            $container->get('utils')->shutdown();
        });
    }

    /**
     * Run all of the above, sequentially
     */
    public static function init()
    {
        self::setupPreConfigEnvironment();
        self::setupAutoLoaders();
        $container = self::setupServiceContainer();
        self::loadConfigDatabaseAndCheckForSetup($container);
        self::setupAuthErrorAndExceptionHandlers($container);
        self::setupTearDown($container);

        return $container;
    }
}
