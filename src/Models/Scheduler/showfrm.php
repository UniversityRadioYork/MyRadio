<?php

/**
 *
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130727
 * @package MyURY_Scheduler
 */
$form = (new MyURYForm('sched_show', 'Scheduler', 'doShow', array(
    'debug' => true,
    'title' => 'Edit Show'
        )
        ))->addField(
                new MyURYFormField('grp-basics', MyURYFormField::TYPE_SECTION, array('label' => 'About My Show'))
        )->addField(
                new MyURYFormField('title', MyURYFormField::TYPE_TEXT, array(
            'explanation' => 'Enter a name for your new show. Try and make it unique.',
            'label' => 'Show Name'
                )
                )
        )->addField(
                new MyURYFormField('description', MyURYFormField::TYPE_BLOCKTEXT, array(
            'explanation' => 'Describe your show as best you can. This goes on the public-facing website.',
            'label' => 'Description'
                )
                )
        )->addField(
                new MyURYFormField('genres', MyURYFormField::TYPE_SELECT, array(
            'options' => array_merge(array(array('text' => 'Please select...', 'disabled' => true)), MyURY_Scheduler::getGenres()),
            'repeating' => true,
            'label' => 'Genre',
            'explanation' => 'What type of music do you play, if any?'
                )
                )
        )->addField(
                new MyURYFormField('tags', MyURYFormField::TYPE_TEXT, array(
            'label' => 'Tags',
            'explanation' => 'A set of keywords to describe your show generally, seperated with spaces.'
                )
                )
        )->addField(
                new MyURYFormField('grp-credits', MyURYFormField::TYPE_SECTION, array('label' => 'Who\'s On My Show'))
        )->addField(
                new MyURYFormField('credits', MyURYFormField::TYPE_TABULARSET, array(
                    'options' => array(
                    new MyURYFormField('member', MyURYFormField::TYPE_MEMBER, array(
                        'explanation' => '',
                        'label' => 'Credit'
                            )
                    ),
                    new MyURYFormField('credittype', MyURYFormField::TYPE_SELECT, array(
                        'options' => array_merge(array(array('text' => 'Please select...', 'disabled' => true)),
                                MyURY_Scheduler::getCreditTypes()),
                        'explanation' => '',
                        'label' => 'Role'
                            )
    ))))
);