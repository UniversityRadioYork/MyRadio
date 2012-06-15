<?php
require 'Views/MyURY/Scheduler/bootstrap.php';

//Add links
foreach ($pending_allocations as $k => $v) {
  $pending_allocations[$k]['summary'] = '<a href="'.CoreUtils::makeURL('Scheduler', 'manageShow').'">'.$pending_allocations[$k]['summary'].'</a>';
}

$twig->setTemplate('table.twig')
        ->addVariable('tablescript', 'myury.scheduler.pending')
        ->addVariable('heading', 'Scheduler')
        ->addVariable('tabledata', $pending_allocations)
        ->render();