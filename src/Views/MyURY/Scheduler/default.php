<?php
require 'Views/MyURY/Scheduler/bootstrap.php';

$twig->setTemplate('table.twig')
        ->addVariable('tablescript', 'myury.scheduler.pending')
        ->addVariable('heading', 'Scheduler')
        ->addVariable('tabledata', CoreUtils::setToDataSource($pending_allocations))
        ->render();