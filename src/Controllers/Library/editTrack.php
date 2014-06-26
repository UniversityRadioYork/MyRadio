<?php
/**
 * Allows URY Librarians to create edit Tracks
 *
 * @author Andy Durant <aj@ury.org.uk>
 * @version 20140626
 * @package MyRadio_Library
 */

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
