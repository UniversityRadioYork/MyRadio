<?php
/**
 *
 * @todo Proper Documentation
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130728
 * @package MyRadio_Scheduler
 */

//The Form definition
require 'Models/Scheduler/showfrm.php';

try {
  MyRadio_Show::create($form->readValues());
} catch (MyRadioException $e) {
  require 'Views/Errors/500.php';
  exit;
}

header('Location: '.CoreUtils::makeURL('Scheduler', 'myShows'));
