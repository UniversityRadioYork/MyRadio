<?php
/**
 * Allows a User to edit an iTones Playlist
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130712
 * @package MyURY_iTones
 */

if (empty($_REQUEST['playlistid'])) throw new MyURYException('No Playlist ID provided.', 400);

$playlist = iTones_Playlist::getInstance($_REQUEST['playlistid']);

$lock = $playlist->acquireOrRenewLock(empty($_SESSION['itones_lock_'.$playlist->getID()])
        ? null : $_SESSION['itones_lock_'.$playlist->getID()]);

if ($lock === false) {
  CoreUtils::getTemplateObject()
          ->setTemplate('error.twig')
          ->addVariable('body', 'Sorry, this playlist is currently being edited by someone else.')
          ->render();
} else {
  $_SESSION['itones_lock_'.$playlist->getID()] = $lock;
  //The Form definition
  require 'Models/iTones/editplaylistfrm.php';

  $tracks = $playlist->getTracks();
  $artists = array();
  foreach ($tracks as $track) {$artists[] = $track->getArtist();}
  $form->setFieldValue('tracks.track', $tracks)
        ->setFieldValue('tracks.artist', $artists)
        ->render();
}

var_dump($form->readValues());