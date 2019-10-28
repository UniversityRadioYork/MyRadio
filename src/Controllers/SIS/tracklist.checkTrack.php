<?php
/**
 * Tracklist Track Inserter for SIS.
 */
use \MyRadio\SIS\SIS_Tracklist;
use \MyRadio\ServiceAPI\MyRadio_Track;
use \MyRadio\MyRadioException;

$artist = $_REQUEST['artist'];
$album = $_REQUEST['album'];
$tname = $_REQUEST['title'];
$trackid = $_REQUEST['trackid'];

$timeslotid = $_SESSION['timeslotid'];

if (empty($trackid)) {
    if (empty($artist)) {
        throw new MyRadioException('Artist is required', 400);
    }
    if (empty($album)) {
        throw new MyRadioException('Album is required', 400);
    }
    if (empty($tname)) {
        throw new MyRadioException('Title is required', 400);
    }
    SIS_Tracklist::insertTrackNoRec($tname, $artist, $album, 'm', $timeslotid);
} else {
    $track = MyRadio_Track::getInstance($trackid);
    SIS_Tracklist::insertTrackRec($track, 'm', $timeslotid);
}
