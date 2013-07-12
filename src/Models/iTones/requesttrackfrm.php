<?php

/**
 * Enables a user to request tracks
 *
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130712
 * @package MyURY_iTones
 */
$form = new MyURYForm('itones_trackrequest', $module, 'doRequestTrack',
                array(
                    'debug' => true,
                    'title' => 'Request Campus Jukebox Track'
        ));

$form->addField(
        new MyURYFormField('track', MyURYFormField::TYPE_TRACK, array(
                        'label' => 'Track',
                        'explanation' => 'Enter a track here to ask our jukebox legumes to queue it up for you.'
                    ))
);