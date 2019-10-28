<?php
/**
 * Ajax request to increment track viewer counter.
 */
use \MyRadio\MyRadio\URLUtils;
use \MyRadio\ServiceAPI\MyRadio_User;
use \MyRadio\ServiceAPI\MyRadio_Webcam;
use \MyRadio\MyRadioException;

if (isset($_SESSION['webcam_lastcounterincrement']) && $_SESSION['webcam_lastcounterincrement'] > time() - 10) {
    // Occurs when browser wakes up and tries to spam all the missed updates, or if multiple webcam pages are open.
    $data = MyRadio_Webcam::getViewCounter(MyRadio_User::getInstance());
} else {
    $_SESSION['webcam_lastcounterincrement'] = time();
    $data = MyRadio_Webcam::incrementViewCounter(MyRadio_User::getInstance());
}

URLUtils::dataToJSON($data);
