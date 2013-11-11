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

$result = MyRadio_Track::findByOptions(
	array('title' => $tname,
	'artist' => $artist,
	'album' => $album,
	'digitised' => false)
	);
$dataout = array();

if ($box == "artist"){
	foreach ($result as $track) {
		$dataout[] = "{$track->getArtist()}";
	}
}
else if ($box == "album"){
	foreach ($result as $track) {
		$dataout[] = "{$track->getAlbum()->getTitle()}";
	}
}
else if ($box == "tname"){
	foreach ($result as $track) {
		$dataout[] = "{$track->getTitle()}";
	}
}

$data = $dataout;
require 'Views/MyRadio/datatojson.php';