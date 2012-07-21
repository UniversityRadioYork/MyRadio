<?php
/**
 * 
 * @todo Proper Documentation
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 21072012
 * @package MyURY_Scheduler
 */
$pending_allocations = Scheduler::getPendingAllocations();

for ($i = 0; $i < sizeof($pending_allocations); $i++) {
  $pending_allocations[$i]['createddate'] = CoreUtils::happyTime($pending_allocations[$i]['createddate'], false);
}