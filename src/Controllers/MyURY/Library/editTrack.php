<?php
/**
 * Allows URY Librarians  to create edit Tracks
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 25042013
 * @package MyURY_Library
 */

//The Form definition
require 'Models/MyURY/Library/trackfrm.php';

$track = MyURY_Track::getInstance($_REQUEST['trackid']);

$form->setFieldValue('title', $track->getTitle());
$form->setFieldValue('artist', $track->getArtist());
$form->setFieldValue('trackid', $track->getID());

$form->render();