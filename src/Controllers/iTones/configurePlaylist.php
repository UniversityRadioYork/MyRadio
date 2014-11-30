<?php
/**
 * Allows a User to configure an iTones Playlist
 *
 * @package MyRadio_iTones
 */

use \MyRadio\MyRadioException;
use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\iTones\iTones_Playlist;
use \MyRadio\iTones\iTones_PlaylistAvailability;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //Submitted
    $data = iTones_Playlist::getForm()->readValues();

    if (empty($data['id'])) {
        throw new MyRadioException('No Playlist ID provided.', 400);
    }

    $playlist = iTones_Playlist::getInstance($data['id']);

    $playlist->setTitle($data['title']);
    $playlist->setDescription($data['description']);
    CoreUtils::backWithMessage('The playlist has been updated.');

} else {
    //Not Submitted
    if (empty($_REQUEST['playlistid'])) {
        throw new MyRadioException('No Playlist ID provided.', 400);
    }

    $playlist = iTones_Playlist::getInstance($_REQUEST['playlistid']);
    $playlist->getEditForm()
            ->setTemplate('iTones/configurePlaylist.twig')
            ->render(array(
                'tabledata' => CoreUtils::dataSourceParser(
                    iTones_PlaylistAvailability::getAvailabilitiesForPlaylist($playlist->getID())
                ),
                'playlistid' => $_REQUEST['playlistid']
            ));
}
