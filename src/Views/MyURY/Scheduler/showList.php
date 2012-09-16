<?php

require 'Views/MyURY/Scheduler/bootstrap.php';

$tabledata = array();
foreach ($shows as $show) {
  $tabledata[] = array(
      'title' => $show->getMeta('title'),
      'seasons' => $show->getNumberOfSeasons(),
      'editlink' => array(
          'display' => 'icon',
          'value' => 'script',
          'title' => 'Edit Show',
          'url' => CoreUtils::makeURL('Scheduler', 'editShow', array('showid' => $show->getID()))),
      'applylink' => array('display' => 'icon',
          'value' => 'calendar',
          'title' => 'Apply for a new Season',
          'url' => CoreUtils::makeURL('Scheduler', 'createSeason', array('showid' => $show->getID()))),
      'micrositelink' => array('display' => 'icon',
          'value' => 'extlink',
          'title' => 'View Show Microsite',
          'url' => $show->getWebpage())
  );
}

$twig->setTemplate('table.twig')
        ->addVariable('heading', 'My Shows')
        ->addVariable('tabledata', $tabledata)
        ->addVariable('tablescript', 'myury.scheduler.showlist');
if (isset($_REQUEST['msg'])) {
  switch($_REQUEST['msg']) {
    case 'seasonCreated':
      $twig->addInfo('Your season application has been submitted for processing.');
      break;
  }
}

$twig->render();