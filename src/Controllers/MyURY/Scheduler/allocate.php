<?php
/**
 * 
 * @todo Proper Documentation
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 22092012
 * @package MyURY_Scheduler
 */

//Model: The Season to be allocated
$season = MyURY_Season::getInstance((int)$_REQUEST['show_season_id']);
//Model: The Form definition
require 'Models/MyURY/Scheduler/allocatefrm.php';
//View: The Form output with $season meta
$form->render($season->toDataSource());