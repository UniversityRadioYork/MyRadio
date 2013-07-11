<?php
require 'Views/Scheduler/bootstrap.php';
$twig->setTemplate('table.twig')
        ->addVariable('tablescript', 'myury.scheduler.pending')
        ->addVariable('title', 'Scheduler')
        ->addVariable('tabledata', ServiceAPI::setToDataSource($pending_allocations))
        ->render();