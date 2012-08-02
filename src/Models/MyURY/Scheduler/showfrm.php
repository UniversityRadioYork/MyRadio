<?php
/**
 * 
 * @todo Proper Documentation
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 21072012
 * @package MyURY_Scheduler
 */
$form = new MyURYForm('sched_allocate', $module, 'doShow',
                array(
                    'debug' => true,
                    'title' => 'Edit Show'
                //'template' => 'MyURY/Scheduler/allocate.twig'
        ));

$form->addField(
        new MyURYFormField('title', MyURYFormField::TYPE_TEXT,
                array(
                    'explanation' => 'Enter a name for your new show. Try and make it unique.',
                    'label' => 'Show Name'
                )
              )
        );