<?php

/**
 *
 * @todo Proper Documentation
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 21072012
 * @package MyURY_Scheduler
 */
$form = (new MyURYForm('sched_season', $module, 'doSeason',
                array(
                    'debug' => true,
                    'title' => 'Edit Season'
                )
        ))->addField(
                new MyURYFormField('grp-basics', MyURYFormField::TYPE_SECTION,
                        array('label' => 'Season Basics'))
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