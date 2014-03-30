<?php
/**
 *
 * @todo Proper Documentation
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 22092012
 * @package MyRadio_Scheduler
 */

//Model: The Season to be allocated
$season = MyRadio_Season::getInstance((int)$_REQUEST['show_season_id']);
/**
 * @todo WHY IS THIS IN THE SESSION
 */
$_SESSION['myury_working_with_season'] = $season->getID();
//Model: The Form definition
require 'Models/Scheduler/allocatefrm.php';
//View: The Form output with $season meta
$form->render($season->toDataSource());
