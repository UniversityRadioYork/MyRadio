<?php
/**
 * Webcam Getter for SIS
 * 
 * @author Andy Durant <aj@ury.org.uk>
 * @version 20131101
 * @package MyRadio_SIS
 */

// @todo: remove the need for this function after migration to sis4 by makeig camserver use streamids
function cam_to_stream($id) {
	switch ($id) {
		case '0': return 1;
		case '1': return 2;
		case '2': return 4;
		case '4': return 5;
		case '5': return 3;
	};
}

$current = file_get_contents("http://copperbox.york.ac.uk:9090/current?noprotocol=true");
    
switch ($current) {
  case '0': $location = 'Jukebox';
    break;
  case '1': $location = 'Studio 1';
    break;
  case '2': $location = 'Studio 2';
    break;
  case '4': $location = 'Office';
    break;
  case '5': $location = 'Studio 1 Secondary';
    break;
  default: $location = $current;
    $current = 6;
    break;
}

$data = array(
  'current' => cam_to_stream($current),
  'webcam' => $location
  );

require 'Views/MyRadio/datatojson.php';