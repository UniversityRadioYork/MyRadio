<?php
/**
 * 
 * @todo Proper Documentation
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 21072012
 * @package MyURY_Scheduler
 */

require 'Models/MyURY/Scheduler/notices.php';
$pending_allocations = MyURY_Scheduler::getPendingAllocations();
require 'Views/MyURY/Scheduler/default.php';