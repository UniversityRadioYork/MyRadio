<?php

require 'Views/MyURY/Scheduler/bootstrap.php';

$tabledata = array();
foreach ($demos as $demo) {
  $tabledata[] = array_merge($demo,array(
      'applylink' => array('display' => 'icon',
          'value' => 'calendar',
          'title' => 'Attend this demo',
          'url' => CoreUtils::makeURL('Scheduler', 'attendDemo', array('demoid' => $demo['show_season_timeslot_id']))))
  );
}

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