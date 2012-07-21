<?php
//This model loads the little notices at the top of the scheduler mainpage

$to_allocate = Scheduler::countPendingAllocations();
$disputes = Scheduler::countPendingDisputes();