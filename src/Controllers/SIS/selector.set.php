<?php
/**
 * Selector setter for SIS
 * 
 * @author Andy Durant <aj@ury.org.uk>
 * @version 20131117
 * @package MyRadio_SIS
 */

$src = (isset($_REQUEST['src'])) ? (int) $_REQUEST['src'] : 0;
$status = MyRadio_Selector::getStatusAtTime(time());

if (($src <= 0) || ($src > 8)) {
  $data = ['error' => 'Invalid Selection'];
  require 'Views/MyRadio/datatojson.php';
}
elseif ($src == $status['studio']) {
  $data = ['error' => 'Already Selected'];
  require 'Views/MyRadio/datatojson.php';
}
elseif ((($src == 1) && (!$status['s1power'])) ||
	(($src == 2) && (!$status['s2power'])) ||
	(($src == 4) && (!$status['s4power']))) {
  $data = ['error' => 'Source not powered'];
  require 'Views/MyRadio/datatojson.php';
}
elseif ($status['locked'] != 0) {
  $data = ['error' => 'locked'];
  require 'Views/MyRadio/datatojson.php';
}
else {
  $response = MyRadio_Selector::setStudio($src);

  if (!empty($response)) {
  	$data = $response;
  	require 'Views/MyRadio/datatojson.php';
  }
  else {
  	header('HTTP/1.1 204 No Content');
  }
}