<?php
/**
 * The default page of the Scheduler module lists Season applications
 * that have not yet had timeslots assigned.
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130923
 * @package MyRadio_Scheduler
 */

CoreUtils::getTemplateObject()->setTemplate('table.twig')
        ->addVariable('tablescript', 'myury.scheduler.pending')
        ->addVariable('title', 'Scheduler')
        ->addVariable('tabledata', CoreUtils::dataSourceParser(
                MyRadio_Scheduler::getPendingAllocations(), false))
        ->render();
