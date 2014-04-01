<?php

/**
 * Returns the APC upload progress data for the given upload ID
 *
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130816
 * @package MyRadio_Core
 */
if (function_exists("uploadprogress_get_info")) {
    $data = uploadprogress_get_info($_REQUEST['id']);
} else {
    trigger_error('uploadprogress PECL extension is not installed.');
    $data = false;
}

require 'Views/MyRadio/datatojson.php';
