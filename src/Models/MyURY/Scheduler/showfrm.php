<?php

/**
 *
 * @todo Proper Documentation
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 21072012
 * @package MyURY_Scheduler
 */
$form = (new MyURYForm('sched_allocate', $module, 'doShow',
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
                            'explanation' => 'Describe your show in as much detail as you can. Minimum 280 characters.',
                            'label' => 'Description',
                            'options' => array('minlength' => 280)
                        )
                )
        )->addField(
                new MyURYFormField('genres', MyURYFormField::TYPE_SELECT,
                        array(
                            'options' => array_merge(array(array('text' => 'Please select...', 'disabled' => true)), Scheduler::getGenres()),
                            'repeating' => true,
                            'label' => 'Genres',
                            'explanation' => 'What type of music do you play, if any?'
                        )
                )
        )->addField(
                new MyURYFormField('grp-credits', MyURYFormField::TYPE_SECTION,
                        array('label' => 'Who\'s On My Show'))
        )->addField(
        new MyURYFormField('presenters', MyURYFormField::TYPE_MEMBER,
                array(
                    'explanation' => 'Who\'s going to be on your new show? We\'ve taken the liberty of guessing you are.',
                    'label' => 'Presenters',
                    'repeating' => true
                )
        )
);