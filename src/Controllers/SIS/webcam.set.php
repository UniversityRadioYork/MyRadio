<?php

if (!isset($_REQUEST['src']))
  return;

// @todo: remove the need for this function after migration to sis4 by makeig camserver use streamids
function cam_to_stream($id) {
	switch ($id) {
		case '1': return 0;
		case '2': return 1;
		case '3': return 5;
		case '4': return 2;
		case '5': return 4;
	};
}

$src = cam_to_stream($_REQUEST['src']);

if (($src === 0) || ($src === 1) || ($src === 2) || ($src === 4) || ($src === 5)) {
  file_get_contents("http://copperbox.york.ac.uk:9090/set?newcam=$src");
}

$data = array(
  'status' => 'ok',
  'payload' => null
  );
require 'Views/MyURY/datatojson.php';