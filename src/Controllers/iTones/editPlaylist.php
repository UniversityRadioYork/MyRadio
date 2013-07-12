<?php
/**
 * Allows a User to edit an iTones Playlist
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130712
 * @package MyURY_iTones
 */

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
  $value = array();
  foreach ($tracks as $track) $value[] = $track->getID();
  $form->setFieldValue('tracks.track', $value)->render();
}