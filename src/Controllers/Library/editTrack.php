<?php
/**
 * Allows URY Librarians to create edit Tracks.
 */
use \MyRadio\MyRadio\URLUtils;
use \MyRadio\ServiceAPI\MyRadio_Track;
use \MyRadio\MyRadioException;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //Submitted
    $data = MyRadio_Track::getForm()->readValues();

    $track = MyRadio_Track::getInstance($data['id']);
    $track->setTitle($data['title']);
    $track->setArtist($data['artist']);
    $track->setAlbum($data['album']);
    $track->setPosition($data['position']);
    $track->setIntro($data['intro']);
    $track->setClean($data['clean']);
    $track->setGenre($data['genre']);
    $track->setDigitised($data['digitised']);
    $track->setBlacklisted($data['blacklisted']);
    $track->setLastEdited();

    URLUtils::backWithMessage('Track Updated.');
} else {
    //Not Submitted
    if (isset($_REQUEST['trackid'])) {
        MyRadio_Track::getInstance($_REQUEST['trackid'])
            ->getEditForm()
            ->render();
    } else {
        throw new MyRadioException('A TrackID to edit has not been provided, please try again.', 400);
    }
}
