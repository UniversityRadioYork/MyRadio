<?php

/**
 *
 * @todo Proper Documentation
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 21072012
 * @package MyRadio_Scheduler
 */

//Set up the weeks checkboxes
$weeks = [];
for ($i = 1; $i <= 10; $i++) {
    $weeks[] = new MyRadioFormField(
        'wk' . $i,
        MyRadioFormField::TYPE_CHECK,
        ['label' => 'Week ' . $i, 'required' => false]
    );
}

$form = (
    new MyRadioForm(
        'sched_season',
        $module,
        'doSeason',
        [
            'debug' => true,
            'title' => 'Edit Season'
        ]
    )
)->addField(
    new MyRadioFormField('show_id', MyRadioFormField::TYPE_HIDDEN)
)->addField(
    new MyRadioFormField(
        'grp-basics',
        MyRadioFormField::TYPE_SECTION,
        ['label' => '']
    )
)->addField(
    new MyRadioFormField(
        'weeks',
        MyRadioFormField::TYPE_CHECKGRP,
        [
            'options' => $weeks,
            'explanation' => 'Select what weeks this term this show will be on air',
            'label' => 'Schedule for Weeks'
        ]
    )
)->addField(
    new MyRadioFormField(
        'times',
        MyRadioFormField::TYPE_TABULARSET,
        [
            'label' => 'Preferred Times',
            'options' => [
                new MyRadioFormField(
                    'day',
                    MyRadioFormField::TYPE_DAY,
                    ['label' => 'On']
                ),
                new MyRadioFormField(
                    'stime',
                    MyRadioFormField::TYPE_TIME,
                    ['label' => 'from']
                ),
                new MyRadioFormField(
                    'etime',
                    MyRadioFormField::TYPE_TIME,
                    ['label' => 'until']
                )
            ]
        ]
    )
)->addField(
    new MyRadioFormField(
        'grp-basics_close',
        MyRadioFormField::TYPE_SECTION_CLOSE
    )
)->addField(
    new MyRadioFormField(
        'grp-adv',
        MyRadioFormField::TYPE_SECTION,
        ['label' => 'Advanced Options']
    )
)->addField(
    new MyRadioFormField(
        'description',
        MyRadioFormField::TYPE_BLOCKTEXT,
        [
            'explanation' => 'Each season of your show can have its own description. '
                .'If you leave this blank, the main description for your Show will be used.',
            'label' => 'Description',
            'options' => ['minlength' => 140],
            'required' => false
        ]
    )
)->addField(
    new MyRadioFormField(
        'tags',
        MyRadioFormField::TYPE_TEXT,
        [
            'label' => 'Tags',
            'explanation' => 'A set of keywords to describe this Season. These will be added onto the '
                .'Tags you already have set for the Show.',
            'required' => false
        ]
    )
)->addField(
    new MyRadioFormField(
        'grp-adv_close',
        MyRadioFormField::TYPE_SECTION_CLOSE
    )
);
