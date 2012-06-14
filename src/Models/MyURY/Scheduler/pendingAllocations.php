<?php

$pending_allocations = Scheduler::getPendingAllocations();

for ($i = 0; $i < sizeof($pending_allocations); $i++) {
  $pending_allocations[$i]['submitted'] = CoreUtils::happyTime($pending_allocations[$i]['submitted']);
}