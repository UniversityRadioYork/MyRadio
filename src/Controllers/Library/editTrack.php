<?php
/**
 * Allows URY Librarians  to create edit Tracks
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130722
 * @package MyURY_Library
 */

//The Form definition
require 'Models/Library/trackfrm.php';

$track = MyURY_Track::getInstance($_REQUEST['trackid']);

$form->editMode($track->getID(),
        array(
            'title' => $track->getTitle(),
            'artist' => $track->getArtist(),
            'albumid' => $track->getAlbum()->getID()
        ))->render();