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

$form = (new MyURYForm('sched_season', $module, 'default',
                array(
                    'debug' => true,
                    'title' => 'Edit Season'
                )
        ))->addField(
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
                new MyURYFormField('grp-times', MyURYFormField::TYPE_SECTION,
                        array('label' => 'Preferred Times'))
        )->addField(new MyURYFormField('day', MyURYFormField::TYPE_DAY,
                        array('repeating' => true, 'label' => ''))
        )->addField(new MyURYFormField('stime', MyURYFormField::TYPE_TIME,
                        array('repeating' => true, 'label' => 'from'))
        )->addField(new MyURYFormField('etime', MyURYFormField::TYPE_TIME,
                        array('repeating' => true, 'label' => 'until'))
        )->addField(
                new MyURYFormField('grp-adv', MyURYFormField::TYPE_SECTION,
                        array('label' => 'Advanced Options'))
        )->addField(
                new MyURYFormField('description', MyURYFormField::TYPE_BLOCKTEXT,
                        array(
                            'explanation' => 'Each season of your show can have it\'s own description. You can edit this later.',
                            'label' => 'Description',
                            'options' => array('minlength' => 140)
                        )
                )
        )->addField(
        new MyURYFormField('tags', MyURYFormField::TYPE_TEXT,
                array(
                    'label' => 'Tags',
                    'explanation' => 'A set of keywords to describe this season, in addition to the ones for your show in general'
                )
        )
);