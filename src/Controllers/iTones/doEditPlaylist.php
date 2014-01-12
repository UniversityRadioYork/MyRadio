<?php
/**
 * Saves changes to an iTones Playlist
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130712
 * @package MyRadio_iTones
 */

require 'Models/iTones/editplaylistfrm.php';

$data = $form->readValues();

if (empty($data['playlistid'])) {
  throw new MyRadioException('No Playlist ID provided.', 400);
}

$playlist = iTones_Playlist::getInstance($data['playlistid']);

if ($playlist->validateLock($_SESSION['itones_lock_'.$playlist->getID()]) === false) {
  CoreUtils::getTemplateObject()
          ->setTemplate('error.twig')
          ->addVariable('body', 'You do not have a valid lock for this playlist or the lock has expired.')
          ->render();
} else {
  $playlist->setTracks($data['tracks']['track'], $_SESSION['itones_lock_'.$playlist->getID()], $data['notes']);
  
  $playlist->releaseLock($_SESSION['itones_lock_'.$playlist->getID()]);
  
  CoreUtils::backWithMessage('The playlist has been updated.');
}