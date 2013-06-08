<?php
/**
 * 
 * @todo Proper Documentation
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 21072012
 * @package MyURY_Scheduler
 */

//The Form definition
require 'Models/Scheduler/demofrm.php';
/**
 * @todo make this less horrific
 */
$demoinfo = $form->readValues();
MyURY_Demo::registerDemo($demoinfo['demo-datetime']);
header('Location: '.CoreUtils::makeURL('Scheduler','listDemos'));