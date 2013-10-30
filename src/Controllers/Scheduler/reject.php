<?php
/**
 * 
 * @todo Proper Documentation
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 02012013
 * @package MyRadio_Scheduler
 */

//Model: The Season to be rejected
$season = MyRadio_Season::getInstance((int)$_REQUEST['show_season_id']);
//Model: The Form definition
require 'Models/Scheduler/rejectfrm.php';
$form->setFieldValue('season_id', $season->getID());
//View: The Form
$form->render();