<?php
/**
 * Allows a User to edit an iTones Playlist
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130712
 * @package MyURY_iTones
 */

//The Form definition
require 'Models/iTones/editplaylistfrm.php';

$tracks = iTones_Playlist::getInstance($_REQUEST['playlistid'])->getTracks();
$value = array();
foreach ($tracks as $track) $value[] = $track->getID();
$form->setFieldValue('tracks.track', $value)->render();