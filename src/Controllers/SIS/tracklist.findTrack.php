<?php
/**
 * Tracklist Track Finder for SIS
 *
 * @author  Andy Durant <aj@ury.org.uk>
 * @version 20131101
 * @package MyRadio_SIS
 */

use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\ServiceAPI\Artist;
use \MyRadio\ServiceAPI\MyRadio_Track;
use \MyRadio\ServiceAPI\MyRadio_Album;

$artist = $_GET['artist'];
$album = $_GET['album'];
$tname = $_GET['tname'];
$box = $_GET['box'];

$artistResult = Artist::findByOptions(
    [
        'title' => $tname,
        'artist' => $artist,
        'album' => $album,
        'digitised' => false
    ]
);
$trackResult = MyRadio_Track::findByOptions(
    [
        'title' => $tname,
        'artist' => $artist,
        'album' => $album,
        'digitised' => false
    ]
);
$albumResult = MyRadio_Album::findByOptions(
    [
        'title' => $tname,
        'artist' => $artist,
        'album' => $album,
        'digitised' => false
    ]
);

$dataout = [];

if ($box == "artist") {
    foreach ($artistResult as $artist) {
        $dataout[] = "{$artist['artist']}";
    }
} elseif ($box == "album") {
    foreach ($albumResult as $record) {
        $dataout[] = "{$record->getTitle()}";
    }
} elseif ($box == "tname") {
    foreach ($trackResult as $track) {
        $dataout[] = "{$track->getTitle()}";
    }
}

$data = $dataout;
CoreUtils::dataToJSON($data);
