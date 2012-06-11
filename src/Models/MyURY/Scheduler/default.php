<?php

$to_allocate = Scheduler::countPendingAllocations();
$disputes = Scheduler::countPendingDisputes();

$items = array();