<?php

/**
 * As well as eventually becoming a iTones Playlist Editor, this is also the test form for repeating fieldsets.
 *
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130712
 * @package MyRadio_iTones
 */
$form = new MyRadioForm('itones_playlistedit', $module, 'doEditPlaylist', array(
    'title' => 'Edit Campus Jukebox Playlist'
        ));

$form->addField(
        new MyRadioFormField('tracks', MyRadioFormField::TYPE_TABULARSET, array('options' => array(
        new MyRadioFormField('track', MyRadioFormField::TYPE_TRACK, array(
            'label' => 'Tracks'
                )),
        new MyRadioFormField('artist', MyRadioFormField::TYPE_ARTIST, array(
            'label' => 'Artists'
                ))
    )
        )
        )
)->addField(new MyRadioFormField('notes', MyRadioFormField::TYPE_TEXT, array(
    'label' => 'Notes',
    'explanation' => 'Optional. Enter notes aboout this change.',
    'required' => false
        )
        )
)->addField(new MyRadioFormField('playlistid', MyRadioFormField::TYPE_HIDDEN));
