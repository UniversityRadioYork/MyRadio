<?php

/**
 *
 * @todo Proper Documentation
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 25042013
 * @package MyRadio_Library
 */

$form = (
    new MyRadioForm(
        'lib_edittrack',
        $module,
        'doEditTrack',
        [
            'title' => 'Edit Track'
        ]
    )
)->addField(new MyRadioFormField('title', MyRadioFormField::TYPE_TEXT, ['label' => 'Title']))
->addField(new MyRadioFormField('artist', MyRadioFormField::TYPE_TEXT, ['label' => 'Artist']))
->addField(new MyRadioFormField('album', MyRadioFormField::TYPE_ALBUM, ['label' => 'Album']));
