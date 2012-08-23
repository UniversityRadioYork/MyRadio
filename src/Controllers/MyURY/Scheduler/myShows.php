<?php
/**
 * 
 * @todo Proper Documentation
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 21072012
 * @package MyURY_Scheduler
 */

print_r(MyURY_Show::getShowsAttachedToUser());exit;

require 'Models/MyURY/Scheduler/notices.php';
require 'Models/MyURY/Scheduler/pendingAllocations.php';
require 'Views/MyURY/Scheduler/default.php';