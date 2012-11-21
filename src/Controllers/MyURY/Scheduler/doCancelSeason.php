<?php
/**
 * Cancels the rest of a season by ID.
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 12112012
 * @package MyURY_Scheduler
 */

//The Form definition
require 'Models/MyURY/Scheduler/cancelSeason.php';
//'tis a one line view
$data = $form->readValues();
$season = MyURY_Season::getInstance($data['show_season_id'])->cancelRestOfSeason();
header('Location: '.CoreUtils::makeURL('Scheduler', 'cancelSeason'));