<?php

/**
 * A basic text field to enable users to explain why they want to cancel the episode
 *
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 05012013
 * @package MyRadio_Scheduler
 */

$form = (
    new MyRadioForm(
        'sched_cancel',
        $module,
        'doCancelEpisode',
        [
            'debug' => false,
            'title' => 'Cancel Episode'
        ]
    )
)->addField(
    new MyRadioFormField(
        'reason',
        MyRadioFormField::TYPE_BLOCKTEXT,
        ['label' => 'Please explain why this Episode should be removed from the Schedule']
    )
)->addField(
    new MyRadioFormField(
        'show_season_timeslot_id',
        MyRadioFormField::TYPE_HIDDEN,
        ['value' => $_REQUEST['show_season_timeslot_id']]
    )
);
