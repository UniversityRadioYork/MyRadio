<?php
/**
 * This is the magic form that makes URY actually have content - it enables users to apply for seasons. And stuff.
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 23082012
 * @package MyURY_Scheduler
 */

//The Form definition
$current_term_id = Scheduler::getActiveApplicationTermInfo();
require 'Models/MyURY/Scheduler/seasonfrm.php';
require 'Views/MyURY/Scheduler/createSeason.php';