<?php
/**
 * 
 * @todo Proper Documentation
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 21072012
 * @package MyURY_Scheduler
 */
CoreUtils::requirePermission(AUTH_ALLOCATESLOTS);

//The standard Scheduler Notices
require 'Models/MyURY/Scheduler/notices.php';
//The Entry to be edited
require 'Models/MyURY/Scheduler/entry.php';
//The Form definition
require 'Models/MyURY/Scheduler/allocatefrm.php';
require 'Views/MyURY/Scheduler/allocate.php';