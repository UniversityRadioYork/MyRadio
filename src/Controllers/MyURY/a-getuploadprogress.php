<?php

/**
 * Returns the APC upload progress data for the given upload ID
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130816
 * @package MyURY_Core
 */
if (!function_exists("apc_fetch")) {
  trigger_error('The APC extension is not installed.');
} else if (!ini_get("apc.rfc1867")) {
  trigger_error('apc.rfc1867 is not enabled. ' . ini_get("apc.rfc1867"));
}

$data = apc_fetch('upload_' . $_REQUEST['id']);

require 'Views/MyURY/datatojson.php';