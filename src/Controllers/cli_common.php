<?php
/**
 * Sets up the environment for MyRadio non-web Controllers
 */

use \MyRadio\Config;

ini_set('include_path', str_replace('Controllers', '', __DIR__) . ':' . ini_get('include_path'));
require_once 'Classes/MyRadio/CoreUtils.php';
require_once 'Classes/Config.php';
require 'MyRadio_Config.local.php';

date_default_timezone_set(Config::$timezone);
require 'Models/Core/api.php';
require_once 'Classes/MyRadioEmail.php';
