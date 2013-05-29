<?php

/**
 * Provides a form to upload presenter photos for a show
 *
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130529
 * @package MyURY_Scheduler
 */
$form = (new MyURYForm('sched_showphoto', $module, 'doShowPhoto', array(
    'debug' => true,
    'title' => 'Update Show Photo',
        )))->addField(
                new MyURYFormField('show_id', MyURYFormField::TYPE_HIDDEN)
        )->addField(
        new MyURYFormField('image_file', MyURYFormField::TYPE_FILE, array('label' => 'Photo')
        )
);