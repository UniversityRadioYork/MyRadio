<?php
/**
 * Webcam Setter for SIS
 * 
 * @author Andy Durant <aj@ury.org.uk>
 * @version 20131101
 * @package MyRadio_SIS
 */

if (!isset($_REQUEST['src']))
  return;

MyRadio_Webcam::setWebcam($_REQUEST['src']);

$data = array(
  'status' => 'ok',
  'payload' => null
  );
require 'Views/MyRadio/datatojson.php';