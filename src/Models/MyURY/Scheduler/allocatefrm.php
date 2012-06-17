<?php
$form = new MyURYForm('sched_allocate', 'Scheduler', 'doAllocate',
        array(
            'debug' => true,
            'template' => 'MyURY/Scheduler/allocate.twig'
        ));