<?php
/**
 * Ajaj request to increment track viewer counter
 */

if (isset($_SESSION['webcam_lastcounterincrement']) && $_SESSION['webcam_lastcounterincrement'] < time()-10) {
  require 'Controllers/Errors/400.php';
}
$_SESSION['webcam_lastcounterincrement'] = time();

$data = MyURY_Webcam::incrementViewCounter(User::getInstance());

require 'Views/MyURY/Core/datatojson.php';