<?php
/**
 * Refreshes a lock on a playlist to prevent it expiring
 *
 * @author  Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130712
 * @package MyRadio_iTones
 */

use \MyRadio\MyRadioException;
use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\iTones\iTones_Playlist;

if (empty($_REQUEST['playlistid'])) {
    throw new MyRadioException('No Playlist ID provided.', 400);
}

$playlist = iTones_Playlist::getInstance($_REQUEST['playlistid']);

$lock = $playlist->acquireOrRenewLock(
    empty($_SESSION['itones_lock_'.$playlist->getID()])
    ? null : $_SESSION['itones_lock_'.$playlist->getID()]
);

if ($lock === false) {
    $data = ['FAIL','Locked for editing by another user'];
} else {
    $_SESSION['itones_lock_'.$playlist->getID()] = $lock;
    $data = ['SUCCESS', $lock];
}

CoreUtils::dataToJSON($data);
