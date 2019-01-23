<?php
/**
 * The default page of the Scheduler module lists Season applications
 * that have not yet had timeslots assigned.
 */
use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\ServiceAPI\MyRadio_Scheduler;

CoreUtils::getTemplateObject()->setTemplate('table.twig')
    ->addVariable('tablescript', 'myradio.scheduler.pending')
    ->addVariable('title', 'Scheduler')
    ->addVariable('subtitle', 'Pending Allocations')
    ->addVariable(
        'tabledata',
        CoreUtils::dataSourceParser(MyRadio_Scheduler::getPendingAllocations())
    )->render();
