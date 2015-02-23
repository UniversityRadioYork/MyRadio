<?php
/**
 * Allows URY Librarians to create edit Tracks
 *
 * @package MyRadio_Library
 */

use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\ServiceAPI\MyRadio_Track;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //Submitted
    $data = MyRadio_Track::getForm()->readValues();

    $track = MyRadio_Track::getInstance($data['id']);
    $track->setTitle($data['title']);
    $track->setArtist($data['artist']);
    $track->setAlbum($data['album']);

    CoreUtils::backWithMessage('Track Updated.');

} else {
    //Not Submitted

    MyRadio_Track::getInstance($_REQUEST['trackid'])
        ->getEditForm()
        ->render();
}
