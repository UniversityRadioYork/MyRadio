<?php
/**
 * Refreshes a lock on a playlist to prevent it expiring
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130712
 * @package MyRadio_iTones
 */

if (empty($_REQUEST['playlistid'])) throw new MyRadioException('No Playlist ID provided.', 400);

$playlist = iTones_Playlist::getInstance($_REQUEST['playlistid']);

$lock = $playlist->acquireOrRenewLock(empty($_SESSION['itones_lock_'.$playlist->getID()])
        ? null : $_SESSION['itones_lock_'.$playlist->getID()]);

if ($lock === false) {
  $data = array('FAIL','Locked for editing by another user');
} else {
  $_SESSION['itones_lock_'.$playlist->getID()] = $lock;
  $data = array('SUCCESS', $lock);
}

require_once 'Views/MyRadio/datatojson.php';