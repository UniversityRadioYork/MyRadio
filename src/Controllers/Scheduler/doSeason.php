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
  $values = $form->readValues();
  MyURY_Season::apply($values);
  header('Location: '.CoreUtils::makeURL('Scheduler', 'listSeasons',
        array('msg' => 'seasonCreated', 'showid' => $values['show_id'])));
} catch (MyURYException $e) {
  require 'Views/Errors/500.php';
}