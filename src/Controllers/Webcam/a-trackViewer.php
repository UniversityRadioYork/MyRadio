<?php
/**
 * Ajax request to increment track viewer counter.
 */
use \MyRadio\MyRadio\URLUtils;
use \MyRadio\ServiceAPI\MyRadio_User;
use \MyRadio\ServiceAPI\MyRadio_Webcam;
use \MyRadio\MyRadioException;

if (isset($_SESSION['webcam_lastcounterincrement']) && $_SESSION['webcam_lastcounterincrement'] > time() - 10) {
    // Occurs when browser wakes up and tries to spam all the missed updates.
    throw new MyRadioException("The Webcam counter was last updated too recently, this request was ingored.", 400);
}
$_SESSION['webcam_lastcounterincrement'] = time();

$data = MyRadio_Webcam::incrementViewCounter(MyRadio_User::getInstance());

URLUtils::dataToJSON($data);
