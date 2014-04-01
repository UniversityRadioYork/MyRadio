<?php

/**
 * Provides a form to upload presenter photos for a show
 *
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130529
 * @package MyRadio_Scheduler
 */
$form = (
    new MyRadioForm(
        'sched_showphoto',
        $module,
        'doShowPhoto',
        array(
            'debug' => true,
            'title' => 'Update Show Photo',
        )
    )
)->addField(
    new MyRadioFormField(
        'show_id',
        MyRadioFormField::TYPE_HIDDEN
    )
)->addField(
    new MyRadioFormField(
        'image_file',
        MyRadioFormField::TYPE_FILE,
        array('label' => 'Photo')
    )
);
