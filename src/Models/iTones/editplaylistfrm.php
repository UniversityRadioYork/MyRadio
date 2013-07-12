<?php

/**
 * As well as eventually becoming a iTones Playlist Editor, this is also the test form for repeating fieldsets.
 *
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130712
 * @package MyURY_iTones
 */
$form = new MyURYForm('itones_playlistedit', $module, 'editPlaylist',
                array(
                    'debug' => true,
                    'title' => 'Edit Campus Jukebox Playlist'
        ));

$form->addField(
        new MyURYFormField('tracks', MyURYFormField::TYPE_TABULARSET,
                array('options' => array(
                    new MyURYFormField('track', MyURYFormField::TYPE_TRACK, array(
                        'label' => 'Tracks',
                        'value' => array(75416, 68305)
                    )),
                    new MyURYFormField('artist', MyURYFormField::TYPE_ARTIST, array(
                        'label' => 'Artists'
                    ))
                    )
                )
        )
);