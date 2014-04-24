<?php
/**
 * Allows a User to edit an iTones Playlist
 *
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130712
 * @package MyRadio_iTones
 */

$form = (
    new MyRadioForm(
        'itones_playlistedit',
        $module,
        $action,
        array(
            'title' => 'Edit Campus Jukebox Playlist'
        )
    )
)->addField(
    new MyRadioFormField(
        'tracks',
        MyRadioFormField::TYPE_TABULARSET,
        array(
            'options' => array(
                new MyRadioFormField(
                    'track',
                    MyRadioFormField::TYPE_TRACK,
                    array(
                        'label' => 'Tracks'
                    )
                ),
                new MyRadioFormField(
                    'artist',
                    MyRadioFormField::TYPE_ARTIST,
                    array(
                        'label' => 'Artists'
                    )
                )
            )
        )
    )
)->addField(
    new MyRadioFormField(
        'notes',
        MyRadioFormField::TYPE_TEXT,
        array(
            'label' => 'Notes',
            'explanation' => 'Optional. Enter notes aboout this change.',
            'required' => false
        )
    )
)->addField(
    new MyRadioFormField(
        'playlistid',
        MyRadioFormField::TYPE_HIDDEN
    )
);


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //Submitted
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

        $tracks = $playlist->getTracks();
        $artists = array();
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
}
