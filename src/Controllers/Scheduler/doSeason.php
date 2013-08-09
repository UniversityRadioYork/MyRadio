<?php
/**
 * 
 * @todo Proper Documentation
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 21072012
 * @package MyURY_Scheduler
 */

//The Form definition
require 'Models/Scheduler/seasonfrm.php';

try {
  MyURY_Season::apply($form->readValues());
} catch (MyURYException $e) {
  require 'Views/Errors/500.php';
  exit;
}

header('Location: '.CoreUtils::makeURL('Scheduler', 'listSeasons',
        array('msg' => 'seasonCreated', 'showid' => $form->readValues()['show_id'])));