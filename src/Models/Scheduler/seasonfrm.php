<?php

/**
 *
 * @todo Proper Documentation
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 21072012
 * @package MyURY_Scheduler
 */

//Set up the weeks checkboxes
$weeks = array();
for ($i = 1; $i <= 10; $i++) {
  $weeks[] = new MyURYFormField('wk' . $i, MyURYFormField::TYPE_CHECK, array('label' => 'Week ' . $i, 'required' => false));
}

$form = (new MyURYForm('sched_season', $module, 'doSeason',
                array(
                    'debug' => true,
                    'title' => 'Edit Season'
                )
        ))->addField(
                new MyURYFormField('show_id', MyURYFormField::TYPE_HIDDEN)
        )->addField(
                new MyURYFormField('grp-basics', MyURYFormField::TYPE_SECTION,
                        array('label' => ''))
        )->addField(
                new MyURYFormField('weeks', MyURYFormField::TYPE_CHECKGRP,
                        array('options' => $weeks,
                            'explanation' => 'Select what weeks this term this show will be on air',
                            'label' => 'Schedule for Weeks'
                        )
                )
        )->addField(
                new MyURYFormField('times', MyURYFormField::TYPE_TABULARSET,
                        array('label' => 'Preferred Times',
                            'options' => array(
                                new MyURYFormField('day', MyURYFormField::TYPE_DAY,
                        array('label' => 'On')),
                                new MyURYFormField('stime', MyURYFormField::TYPE_TIME,
                        array('label' => 'from')),
                                new MyURYFormField('etime', MyURYFormField::TYPE_TIME,
                        array('label' => 'until'))
                            )))
        )->addField(
                new MyURYFormField('grp-adv', MyURYFormField::TYPE_SECTION,
                        array('label' => 'Advanced Options'))
        )->addField(
                new MyURYFormField('description', MyURYFormField::TYPE_BLOCKTEXT,
                        array(
                            'explanation' => 'Each season of your show can have its own description. '
                            . 'If you leave this blank, the main description for your Show will be used.',
                            'label' => 'Description',
                            'options' => array('minlength' => 140),
                            'required' => false
                        )
                )
        )->addField(
        new MyURYFormField('tags', MyURYFormField::TYPE_TEXT,
                array(
                    'label' => 'Tags',
                    'explanation' => 'A set of keywords to describe this Season. These will be added onto the'
                    . ' Tags you already have set for the Show.',
                    'required' => false
                )
        )
);