<?php
require 'Views/MyURY/Scheduler/bootstrap.php';

foreach ($pending_allocations as $k => $v) {
  $pending_allocations[$k]['editlink'] = array(
      'display' => 'text',
      'url' => CoreUtils::makeURL('Scheduler', 'entry'),
      'value' => 'Edit/Allocate'
      );
}

$twig->setTemplate('table.twig')
        ->addVariable('tablescript', 'myury.scheduler.pending')
        ->addVariable('heading', 'Scheduler')
        ->addVariable('tabledata', $pending_allocations)
        ->render();