<?php
/**
 *
 * @todo Proper Documentation
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 21072012
 * @package MyRadio_Scheduler
 */

//The Form definition
require 'Models/Scheduler/demofrm.php';
/**
 * @todo make this less horrific
 */
$demoinfo = $form->readValues();
MyRadio_Demo::registerDemo($demoinfo['demo-datetime']);
CoreUtils::redirect('Scheduler', 'listDemos');
