<?php
/**
 * Saves changes to an iTones Playlist
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130712
 * @package MyURY_iTones
 */

require 'Models/iTones/editplaylistfrm.php';

$data = $form->readValues();

if (empty($data['playlistid'])) throw new MyURYException('No Playlist ID provided.', 400);

$playlist = iTones_Playlist::getInstance($data['playlistid']);

$lock = $playlist->acquireOrRenewLock(empty($_SESSION['itones_lock_'.$playlist->getID()])
        ? null : $_SESSION['itones_lock_'.$playlist->getID()]);

if ($lock === false) {
  CoreUtils::getTemplateObject()
          ->setTemplate('error.twig')
          ->addVariable('body', 'You do not have a valid lock for this playlist or the lock has expired.')
          ->render();
} else {
  $_SESSION['itones_lock_'.$playlist->getID()] = $lock;
  
  $playlist->setTracks($data['tracks']['track'], $lock);
  
  $playlist->releaseLock($lock);
  
  CoreUtils::backWithMessage('The playlist has been updated.');
}