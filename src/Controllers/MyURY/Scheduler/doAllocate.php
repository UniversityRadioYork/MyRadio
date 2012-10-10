<?php
/**
 * This is where the magic happens. This Action takes everything from MyURY Show, Season, Timeslot, Metadata and
 * Scheduling information and turns it into a show that actually goes out on air!
 * 
 * @todo Proper Documentation
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 09102012
 * @package MyURY_Scheduler
 */

//The Form definition
$season = MyURY_Season::getInstance($_SESSION['myury_working_with_season']);
require 'Models/MyURY/Scheduler/allocatefrm.php';
print_r($_REQUEST);
print_r($form->readValues());

throw new MyURYException('Not Implemented', MyURYException::FATAL);