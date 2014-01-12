<?php
/**
 * 
 * @todo Proper Documentation
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 21072012
 * @package MyRadio_Scheduler
 */

//The Form definition
require 'Models/Scheduler/seasonfrm.php';

try {
  $values = $form->readValues();
  MyRadio_Season::apply($values);
  header('Location: '.CoreUtils::makeURL('Scheduler', 'listSeasons',
        array('msg' => 'seasonCreated', 'showid' => $values['show_id'])));
} catch (MyRadioException $e) {
  require 'Views/Errors/500.php';
}