<?php

/**
 * Returns the APC upload progress data for the given upload ID
 *
 * @package MyRadio_Core
 */

use \MyRadio\MyRadio\CoreUtils;

if (function_exists("uploadprogress_get_info")) {
    $data = uploadprogress_get_info($_REQUEST['id']);
} else {
    trigger_error('uploadprogress PECL extension is not installed.');
    $data = false;
}

CoreUtils::dataToJSON($data);
