<?php
/**
 * Allows a User to configure an iTones Playlist
 *
 * @package MyRadio_iTones
 */

use \MyRadio\MyRadioException;
use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\MyRadio\URLUtils;
use \MyRadio\iTones\iTones_Playlist;
use \MyRadio\iTones\iTones_PlaylistAvailability;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //Submitted
    $data = iTones_Playlist::getForm()->readValues();

    if (empty($data['id'])) {
        //Create
        $playlist = iTones_Playlist::create($data['title'], $data['description']);
        URLUtils::redirect(
            'iTones', 'configurePlaylist', [
            'playlistid' => $playlist->getID(),
            'message' => base64_encode('The playlist has been created.')
            ]
        );
    } else {
        //Edit
        $playlist = iTones_Playlist::getInstance($data['id']);

        $playlist->setTitle($data['title']);
        $playlist->setDescription($data['description']);
        URLUtils::backWithMessage('The playlist has been updated.');
    }
} else {
    //Not Submitted
    if (empty($_REQUEST['playlistid'])) {
        //Create
        $playlist = iTones_Playlist::getForm()
                    ->setTemplate('iTones/configurePlaylist.twig')
                    ->render();
    } else {
        //Update
        $playlist = iTones_Playlist::getInstance($_REQUEST['playlistid']);
        $playlist->getEditForm()
            ->setTemplate('iTones/configurePlaylist.twig')
            ->render(
                array(
                    'tabledata' => CoreUtils::dataSourceParser(
                        iTones_PlaylistAvailability::getAvailabilitiesForPlaylist($playlist->getID())
                    ),
                    'playlistid' => $_REQUEST['playlistid']
                    )
            );
    }
}
