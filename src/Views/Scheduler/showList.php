<?php

require 'Views/Scheduler/bootstrap.php';

$twig->setTemplate('Scheduler/showList.twig')
        ->addVariable('title', 'All Shows')
        ->addVariable('tabledata', ServiceAPI::setToDataSource($shows))
        ->addVariable('tablescript', 'myury.scheduler.showlist');
if (isset($_REQUEST['msg'])) {
  switch($_REQUEST['msg']) {
    case 'seasonCreated':
      $twig->addInfo('Your season application has been submitted for processing.');
      break;
  }
}

$twig->render();