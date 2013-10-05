<?php
/**
 * Sets up the environment for MyURY non-web Controllers
 */
ini_set('include_path', str_replace('Controllers', '', __DIR__) . ':' . ini_get('include_path'));
define('SHIBBOBLEH_ALLOW_READONLY', true);
require_once 'shibbobleh_client.php';
require_once 'Classes/MyURY/CoreUtils.php';
require_once 'Classes/Config.php';
require 'MyURY_Config.local.php';

/*
 * If there definitely isn't a logged-in session, then set the System user.
 * However, to also block access to web services in the event this include manages to run rampant.
 * (e.g. the API)
 */
if (!isset($_SESSION['memberid'])) {
  $_SESSION['memberid'] = Config::$system_user;
  $_SESSION['shib_allow_access'] = false;
}
date_default_timezone_set(Config::$timezone);
require 'Models/Core/api.php';
require_once 'Classes/MyURYEmail.php';