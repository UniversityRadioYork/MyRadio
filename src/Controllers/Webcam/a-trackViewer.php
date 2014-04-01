<?php
/**
 * Ajax request to increment track viewer counter
 */

if (isset($_SESSION['webcam_lastcounterincrement']) && $_SESSION['webcam_lastcounterincrement'] > time()-10) {
    require 'Controllers/Errors/400.php';
}
$_SESSION['webcam_lastcounterincrement'] = time();

$data = MyRadio_Webcam::incrementViewCounter(MyRadio_User::getInstance());

require 'Views/MyRadio/datatojson.php';
