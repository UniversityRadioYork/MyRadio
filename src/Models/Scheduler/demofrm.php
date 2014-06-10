<?php

/**
 *
 * @todo Proper Documentation
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 21072012
 * @package MyRadio_Scheduler
 */

$form = (
    new MyRadioForm(
        'sched_demo',
        $module,
        'doDemo',
        [
            'title' => 'Create Demo'
        ]
    )
)->addField(
    new MyRadioFormField(
        'demo-datetime',
        MyRadioFormField::TYPE_DATETIME,
        ['label' => 'Date and Time of the Demo']
    )
);
