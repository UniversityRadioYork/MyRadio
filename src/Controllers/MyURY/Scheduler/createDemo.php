<?php
/**
 * Allows URY Trainers to create demo slots for new members to attend
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 24102012
 * @package MyURY_Scheduler
 */

//The Form definition
require 'Models/MyURY/Scheduler/demofrm.php';
//'tis a one line view
$form->setTemplate('MyURY/Scheduler/createDemo.twig')->render();