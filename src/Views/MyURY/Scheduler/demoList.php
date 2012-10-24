<?php

require 'Views/MyURY/Scheduler/bootstrap.php';

$tabledata = array();
foreach ($demos as $demo) {
  $demo['join'] = '<a href="'.CoreUtils::makeURL('Scheduler', 'attendDemo', array('demoid' => $demo['show_season_timeslot_id'])).'">Join</a>';
  $tabledata[] = $demo;
}
//print_r($tabledata);
$twig->setTemplate('table.twig')
        ->addVariable('heading', 'Upcoming Demo Slots')
        ->addVariable('tabledata', $tabledata)
        ->addVariable('tablescript', 'myury.scheduler.demolist');
if (isset($_REQUEST['msg'])) {
  switch($_REQUEST['msg']) {
    case 'seasonCreated':
      $twig->addInfo('Your season application has been submitted for processing.');
      break;
  }
}

$twig->render();