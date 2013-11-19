<?php
/**
 * Tracklist Track Finder for SIS
 * 
 * @author Andy Durant <aj@ury.org.uk>
 * @version 20131101
 * @package MyRadio_SIS
 */

$artist = $_GET['artist'];
$album = $_GET['album'];
$tname = $_GET['tname'];
$box = $_GET['box'];

$trackResult = MyRadio_Track::findByOptions(
	['title' => $tname,
	'artist' => $artist,
	'album' => $album,
	'digitised' => false]
	);
$albumResult = MyRadio_Album::findByName($album, Config::$ajax_limit_default);

$dataout = array();

if ($box == "artist"){
	foreach ($trackResult as $track) {
		$dataout[] = "{$track->getArtist()}";
	}
}
else if ($box == "album"){
	foreach ($albumResult as $record) {
		$dataout[] = "{$record->getTitle()}";
	}
}
else if ($box == "tname"){
	foreach ($trackResult as $track) {
		$dataout[] = "{$track->getTitle()}";
	}
}

$data = $dataout;
require 'Views/MyRadio/datatojson.php';