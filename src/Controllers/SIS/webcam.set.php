<?php
/**
 * Webcam Setter for SIS
 *
 * @package MyRadio_SIS
 */

use \MyRadio\ServiceAPI\MyRadio_Webcam;

if (!isset($_REQUEST['src'])) {
    return;
}

MyRadio_Webcam::setWebcam($_REQUEST['src']);

header('HTTP/1.1 204 No Content');
