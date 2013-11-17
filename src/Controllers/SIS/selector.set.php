<?php
/**
 * Selector setter for SIS
 * 
 * @author Andy Durant <aj@ury.org.uk>
 * @version 20131117
 * @package MyRadio_SIS
 */

$src = (isset($_REQUEST['src'])) ? (int) $_REQUEST['src'] : 0;

if (($src <= 0) || ($src > 8)) {
  $data = ['myury_errors' => 'Invalid Studio ID'];
  require 'Views/MyRadio/datatojson.php';
}

$status = MyRadio_Selector::getStatusAtTime(time());

if ($src == $status['studio']) {
  $data = ['myury_errors' => 'Already Selected'];
  require 'Views/MyRadio/datatojson.php';
}
if ((($src == 1) && (!$status['s1power'])) ||
	(($src == 2) && (!$status['s2power'])) ||
	(($src == 4) && (!$status['s4power']))) {
  $data = ['myury_errors' => 'Source '.$src.' is not powered'];
  require 'Views/MyRadio/datatojson.php';
}

if ($status['locked'] != 0) {
  $data = ['myury_errors' => 'Selector Locked'];
  require 'Views/MyRadio/datatojson.php';
}

$response = MyRadio_Selector::setStudio($src);

if (!empty($response)) {
	$data = $response;
	require 'Views/MyRadio/datatojson.php';
}
else {
	header('HTTP/1.1 204 No Content');
}