<?php
/**
 * Allows a User to edit an iTones Playlist
 *
 * @author Andy Durant <aj@ury.org.uk>
 * @version 20140636
 * @package MyRadio_iTones
 */

use \MyRadio\MyRadioException;
use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\iTones\iTones_Playlist;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //Submitted
    $data = iTones_Playlist::getForm()->readValues();

    print_r($data);

    if (empty($data['id'])) {
        throw new MyRadioException('No Playlist ID provided.', 400);
    }

    $playlist = iTones_Playlist::getInstance($data['id']);

    if ($playlist->validateLock($_SESSION['itones_lock_'.$playlist->getID()]) === false) {
        CoreUtils::getTemplateObject()
            ->setTemplate('error.twig')
            ->addVariable('body', 'You do not have a valid lock for this playlist or the lock has expired.')
            ->render();
    } else {
        $playlist->setTracks(
            $data['tracks']['track'],
            $_SESSION['itones_lock_'.$playlist->getID()],
            $data['notes']
        );

        $playlist->releaseLock(
            $_SESSION['itones_lock_'.$playlist->getID()]
        );

        CoreUtils::backWithMessage('The playlist has been updated.');
    }

} else {
    //Not Submitted
    if (empty($_REQUEST['playlistid'])) {
        throw new MyRadioException('No Playlist ID provided.', 400);
    }

    $playlist = iTones_Playlist::getInstance($_REQUEST['playlistid']);

    $lock = $playlist->acquireOrRenewLock(
        empty($_SESSION['itones_lock_'.$playlist->getID()])
        ? null : $_SESSION['itones_lock_'.$playlist->getID()]
    );

    if ($lock === false) {
        CoreUtils::getTemplateObject()
            ->setTemplate('error.twig')
            ->addVariable('body', 'Sorry, this playlist is currently being edited by someone else.')
            ->render();
    } else {
        $_SESSION['itones_lock_'.$playlist->getID()] = $lock;

        $playlist->getEditForm()->setTemplate('iTones/editPlaylist.twig')
            ->render();
    }
}
