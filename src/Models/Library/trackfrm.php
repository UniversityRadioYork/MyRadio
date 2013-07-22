<?php

/**
 *
 * @todo Proper Documentation
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 25042013
 * @package MyURY_Library
 */

$form = (new MyURYForm('lib_edittrack', $module, 'doEditTrack',
                array(
                    'title' => 'Edit Track'
                )
        ))->addField(new MyURYFormField('title', MyURYFormField::TYPE_TEXT, array('label' => 'Title of the Track')))
          ->addField(new MyURYFormField('artist', MyURYFormField::TYPE_TEXT, array('label' => 'Artist of the Track')))
          ->addField(new MyURYFormField('album', MyURYFormField::TYPE_TEXT, array('label' => 'Album of the Track')));