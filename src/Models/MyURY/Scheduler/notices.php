<?php
/**
 * 
 * @todo Proper Documentation
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 21072012
 * @package MyURY_Scheduler
 */
//This model loads the little notices at the top of the scheduler mainpage

$to_allocate = Scheduler::countPendingAllocations();
$disputes = Scheduler::countPendingDisputes();