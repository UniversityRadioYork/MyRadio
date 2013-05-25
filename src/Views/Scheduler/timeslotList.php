<?php
require 'Views/Scheduler/bootstrap.php';
$twig->setTemplate('table.twig')
        ->addVariable('tablescript', 'myury.scheduler.timeslotlist')
        ->addVariable('title', 'Episodes of '.$season->getMeta('title'))
        ->addVariable('tabledata', ServiceAPI::setToDataSource($timeslots))
        ->render();