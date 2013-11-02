<?php

//Allow Session writing from other requests
$session = $_SESSION;
session_write_close();

$pollFuncs = SIS_Utils::readPolls(array_merge(SIS_Utils::getPlugins(), SIS_Utils::getTabs()));

//Enter an infinite loop calling these functions, and enjoy the ride
//Times out after 50 cycles to prevent infinites or something like that
$count = 0;
$data = array();
/*do {
	foreach ($pollFuncs as $function) {
		$temp = call_user_func($function, $session);
		if (!empty($temp)) {
			$data = array_merge($data, $temp);
		}
	}
	sleep(1);
	$count++;
} while (empty($data) && $count < 50);*/
$data[] = SIS_Tracklist::getTrackListing($session['timeslotid'], isset($_REQUEST['tracklist_highest_id']) ? $_REQUEST['tracklist_highest_id'] : 0);
$data[] = MyRadio_TracklistItem::getTracklistForTimeslot($session['timeslotid'], 0);
//Return the response data
require 'Views/MyRadio/datatojson.php';