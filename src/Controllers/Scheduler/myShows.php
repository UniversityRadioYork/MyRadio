<?php
/**
 * 
 * @todo Proper Documentation
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130809
 * @package MyURY_Scheduler
 */

$shows = MyURY_Show::getShowsAttachedToUser();

//This is a Joyride start point - if there are now shows, run the first show joyride.
if (empty($shows)) {
  $_SESSION['joyride'] = 'first_show';
}

$twig = CoreUtils::getTemplateObject()->setTemplate('table.twig')
        ->addVariable('title', 'My Shows')
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