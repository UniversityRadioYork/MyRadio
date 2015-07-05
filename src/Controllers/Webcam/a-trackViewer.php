<?php
/**
 * Ajax request to increment track viewer counter
 */

use \MyRadio\MyRadio\URLUtils;
use \MyRadio\ServiceAPI\MyRadio_User;
use \MyRadio\ServiceAPI\MyRadio_Webcam;

if (isset($_SESSION['webcam_lastcounterincrement']) && $_SESSION['webcam_lastcounterincrement'] > time()-10) {
    require 'Controllers/Errors/400.php';
}
$_SESSION['webcam_lastcounterincrement'] = time();

$data = MyRadio_Webcam::incrementViewCounter(MyRadio_User::getInstance());

URLUtils::dataToJSON($data);
