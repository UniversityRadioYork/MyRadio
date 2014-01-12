<?php
/**
 * This is where the magic happens. This Action takes everything from MyRadio Show, Season, Timeslot, Metadata and
 * Scheduling information and turns it into a show that actually goes out on air!
 * 
 * @todo Proper Documentation
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 09102012
 * @package MyRadio_Scheduler
 */

//The Form definition
$season = MyRadio_Season::getInstance($_SESSION['myury_working_with_season']);
require 'Models/Scheduler/allocatefrm.php';
$season->schedule($form->readValues());
require 'Controllers/Scheduler/default.php';