<?php
$form = new MyURYForm('sched_allocate', 'Scheduler', 'doAllocate',
        array(
            'debug' => true,
            'template' => 'MyURY/Scheduler/allocate.twig'
        ));

$form->addField(
        new MyURYFormField('testattribute', MyURYFormField::TYPE_TEXT)
        );