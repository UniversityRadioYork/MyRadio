<?php
/**
 * Sets up the environment for MyURY non-web Controllers
 */
ini_set('include_path', str_replace('Controllers', '', __DIR__) . ':' . ini_get('include_path'));
define('SHIBBOBLEH_ALLOW_READONLY', true);
require_once 'Classes/MyURY/CoreUtils.php';
require_once 'Classes/Config.php';
date_default_timezone_set(Config::$timezone);
require_once 'Classes/MyURYEmail.php';
require 'Models/Core/api.php';