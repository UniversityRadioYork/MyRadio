<?php
/**
 * Allows URY Librarians to create edit Tracks.
 */
use \MyRadio\MyRadio\URLUtils;
use \MyRadio\ServiceAPI\MyRadio_Track;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //Submitted
    $data = MyRadio_Track::getForm()->readValues();

    $track = MyRadio_Track::getInstance($data['id']);
    $track->setTitle($data['title']);
    $track->setArtist($data['artist']);
    $track->setAlbum($data['album']);

    URLUtils::backWithMessage('Track Updated.');
} else {
    //Not Submitted

    MyRadio_Track::getInstance($_REQUEST['trackid'])
        ->getEditForm()
        ->render();
}
