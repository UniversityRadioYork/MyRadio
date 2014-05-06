<?php
/**
 * Allows a User to edit an iTones Playlist
 *
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130712
 * @package MyRadio_iTones
 */

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
    //The Form definition
    require 'Models/iTones/editplaylistfrm.php';

    $tracks = $playlist->getTracks();
    $artists = [];
    foreach ($tracks as $track) {
        if ($track instanceof MyRadio_Track) {
            $artists[] = $track->getArtist();
        }
    }
    $form->setTemplate('iTones/editPlaylist.twig')
        ->setFieldValue('tracks.track', $tracks)
        ->setFieldValue('tracks.artist', $artists)
        ->setFieldValue('playlistid', $playlist->getID())
        ->render();
}
