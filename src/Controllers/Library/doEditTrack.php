<?php
/**
 * Allows URY Librarians  to create edit Tracks
 *
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 25042013
 * @package MyRadio_Library
 */

//The Form definition
require 'Models/Library/trackfrm.php';

$data = $form->readValues();

$track = MyRadio_Track::getInstance($data['id']);
$track->setTitle($data['title']);
$track->setArtist($data['artist']);
$track->setAlbum($data['album']);

require 'Views/MyRadio/back.php';
