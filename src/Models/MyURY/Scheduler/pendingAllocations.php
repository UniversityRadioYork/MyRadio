<?php

$pending_allocations = Scheduler::getPendingAllocations();

for ($i = 0; $i < sizeof($pending_allocations); $i++) {
  $pending_allocations[$i]['createddate'] = CoreUtils::happyTime($pending_allocations[$i]['createddate']);
}