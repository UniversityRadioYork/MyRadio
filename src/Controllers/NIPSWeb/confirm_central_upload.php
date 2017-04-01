<?php
/**
 * Saves a cached upload into the URY Central Database.
 */
use \MyRadio\MyRadio\URLUtils;
use \MyRadio\ServiceAPI\MyRadio_Track;

$data = MyRadio_Track::identifyAndStoreTrack(
    $_REQUEST['fileid'],
    $_REQUEST['title'],
    $_REQUEST['artist'],
    $_REQUEST['album'],
    $_REQUEST['position'],
    filter_var($_REQUEST['explicit'], FILTER_VALIDATE_BOOLEAN) ? true : null
);
$data['fileid'] = $_REQUEST['fileid'];

URLUtils::dataToJSON($data);
