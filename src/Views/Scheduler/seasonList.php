<?php
require 'Views/Scheduler/bootstrap.php';
$twig->setTemplate('table.twig')
        ->addVariable('tablescript', 'myury.scheduler.seasonlist')
        ->addVariable('title', 'Seasons of '.$show->getMeta('title'))
        ->addVariable('tabledata', ServiceAPI::setToDataSource($seasons))
        ->render();