<?php
/**
 * Allows URY Librarians  to create edit Tracks
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 25042013
 * @package MyURY_Library
 */

//The Form definition
require 'Models/Library/trackfrm.php';

$data = $form->readValues();

$track = MyURY_Track::getInstance($data['myuryfrmedid']);
$track->setTitle($data['title']);
$track->setArtist($data['artist']);
$track->setAlbum(MyURY_Album::getInstance($data['album']));

require 'Views/MyURY/back.php';