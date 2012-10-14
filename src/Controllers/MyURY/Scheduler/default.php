<?php
/**
 * 
 * @todo Proper Documentation
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 21072012
 * @package MyURY_Scheduler
 */

$pending_allocations = MyURY_Scheduler::getPendingAllocations();
require 'Views/MyURY/Scheduler/default.php';