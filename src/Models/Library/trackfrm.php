<?php

/**
 *
 * @todo Proper Documentation
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 25042013
 * @package MyRadio_Library
 */

$form = (new MyRadioForm('lib_edittrack', $module, 'doEditTrack',
                array(
                    'title' => 'Edit Track'
                )
        ))->addField(new MyRadioFormField('title', MyRadioFormField::TYPE_TEXT, array('label' => 'Title')))
          ->addField(new MyRadioFormField('artist', MyRadioFormField::TYPE_TEXT, array('label' => 'Artist')))
          ->addField(new MyRadioFormField('album', MyRadioFormField::TYPE_ALBUM, array('label' => 'Album')));