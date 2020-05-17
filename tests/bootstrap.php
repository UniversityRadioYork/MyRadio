<?php

require_once './src/Classes/Autoloader.php';
// instantiate the loader
$loader = new \MyRadio\Autoloader();
// register the autoloader
$loader->register();
// register the base directories for the namespace prefix
$_basepath = './src/';

$loader->addNamespace('MyRadio', $_basepath.'Classes');
$loader->addNamespace('MyRadio\Iface', $_basepath.'Interfaces');

unset($_basepath);

/**
 * Sets up the autoloader for composer.
 */
require './src/vendor/autoload.php';

require_once 'MyRadio_Config.test.php';
