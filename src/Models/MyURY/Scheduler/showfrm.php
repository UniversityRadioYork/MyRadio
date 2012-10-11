<?php

/**
 *
 * @todo Proper Documentation
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 21072012
 * @package MyURY_Scheduler
 */

$form = (new MyURYForm('sched_show', $module, 'doShow',
                array(
                    'debug' => true,
                    'title' => 'Edit Show'
//'template' => 'MyURY/Scheduler/allocate.twig'
                )
        ))->addField(
                new MyURYFormField('grp-basics', MyURYFormField::TYPE_SECTION,
                        array('label' => 'About My Show'))
        )->addField(
                new MyURYFormField('title', MyURYFormField::TYPE_TEXT,
                        array(
                            'explanation' => 'Enter a name for your new show. Try and make it unique.',
                            'label' => 'Show Name'
                        )
                )
        )->addField(
                new MyURYFormField('description', MyURYFormField::TYPE_BLOCKTEXT,
                        array(
                            'explanation' => 'Describe your show as best you can. This goes on the public-facing website.',
                            'label' => 'Description'
                        )
                )
        )->addField(
                new MyURYFormField('genres', MyURYFormField::TYPE_SELECT,
                        array(
                            'options' => array_merge(array(array('text' => 'Please select...', 'disabled' => true)), MyURY_Scheduler::getGenres()),
                            'repeating' => true,
                            'label' => 'Genres',
                            'explanation' => 'What type of music do you play, if any?'
                        )
                )
        )->addField(
                new MyURYFormField('tags', MyURYFormField::TYPE_TEXT,
                        array(
                            'label' => 'Tags',
                            'explanation' => 'A set of keywords to describe your show generally, seperated with spaces.'
                        )
                )
        )->addField(
                new MyURYFormField('grp-credits', MyURYFormField::TYPE_SECTION,
                        array('label' => 'Who\'s On My Show'))
        )->addField(
                new MyURYFormField('credits', MyURYFormField::TYPE_MEMBER,
                        array(
                            'explanation' => '',
                            'label' => '',
                            'repeating' => true,
                            'classes' => array('nobr')
                        )
                )
        )->addField(
        new MyURYFormField('credittypes', MyURYFormField::TYPE_SELECT,
                array(
                    'options' => array_merge(array(array('text' => 'Please select...', 'disabled' => true)), MyURY_Scheduler::getCreditTypes()),
                    'explanation' => '',
                    'label' => '',
                    'repeating' => true,
                    'classes' => array('nobr')
                )
        )
);