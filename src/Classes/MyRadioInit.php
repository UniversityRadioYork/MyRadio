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

use \MyRadio\Database;
use \MyRadio\MyRadioError;
use \MyRadio\ContainerSubject;
use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\MyRadio\MyRadioSession;
use \MyRadio\MyRadio\MyRadioNullSession;

use \Pimple\Container;

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
        define('MYRADIO_CURRENT_SCHEMA_VERSION', 0);
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

        unset($_basepath);

        /**
         * Sets up the autoloader for composer
         */
        require_once 'vendor/autoload.php';
    }

    /**
     * Build a Pimple Container with services. Register it with ServiceAPI.
     * @return Container
     */
    protected static function setupServiceContainer()
    {
        $container = new Container();

        $container['database'] = function($container) {
            return new Database($container['config']);
        };

        $container['cache'] = function($container) {
            $provider = $container['config']->cache_provider;
            return new $provider($container['config']->cache_enable, $container);
        };

        $container['session'] = $container->factory(function($container) {
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
                $session_handler = new MyRadioSession($container['database']);
            } else {
                $session_handler = new MyRadioNullSession;
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
            return $session_handler;
        });

        $container['config'] = function() {
            return new \MyRadio\Config;
        };

        return $container;
    }

    /**
     * Load in MyRadio_Config.local.php and launch setup if necessary.
     *
     * @return boolean false if setup has been loaded. Calling process should halt execution.
     */
    protected static function loadConfigAndCheckForSetup($container)
    {
        /**
         * Load configuration specific to this system.
         * Or, if it doesn't exist, kick into setup.
         */
        if (stream_resolve_include_path('MyRadio_Config.local.php')) {
            require_once 'MyRadio_Config.local.php';
            if ($container['config']->setup === true) {
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
            function ($e) {
                global $container;
                if (method_exists($e, 'uncaught')) {
                    $e->uncaught($container);
                } else {
                    echo 'This information is not available at the moment. Please try again later.';
                }
            }
        );

        // Set error log file
        ini_set('error_log', $container['config']->log_file);

        //Initialise the permission constants
        CoreUtils::setUpAuth();

        /**
         * Turn off visible error reporting, if needed
         * must come after CoreUtils::setUpAuth()
         */
        if (!$container['config']->display_errors && !CoreUtils::hasPermission(AUTH_SHOWERRORS)) {
            ini_set('display_errors', 'Off');
        }
    }

    /**
     * Register shutdown functions
     */
    protected static function setupTearDown()
    {
        register_shutdown_function('\MyRadio\MyRadio\CoreUtils::shutdown');
    }

    /**
     * Run all of the above, sequentially
     */
    public static function init()
    {
        self::setupPreConfigEnvironment();
        self::setupAutoLoaders();
        $container = self::setupServiceContainer();
        ContainerSubject::registerContainer($container);
        self::loadConfigAndCheckForSetup($container);
        self::setupAuthErrorAndExceptionHandlers($container);
        self::setupTearDown();

        return $container;
    }
}
