<?php
require 'Views/MyURY/Scheduler/bootstrap.php';
$twig->setTemplate('table.twig')
        ->addVariable('tablescript', 'myury.scheduler.pending')
        ->addVariable('title', 'Seasons of '.$show->getMeta('title'))
        ->addVariable('tabledata', ServiceAPI::setToDataSource($seasons))
        ->render();