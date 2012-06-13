<?php
require 'Views/MyURY/Scheduler/bootstrap.php';
$twig->setTemplate('table.twig')
        ->addVariable('tablescript', 'schedule_pending')
        ->addVariable('heading', 'Scheduler')
        ->addVariable('tabledata', $pending_allocations)
        ->render();