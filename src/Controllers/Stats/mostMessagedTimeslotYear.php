<?php
/**
 * The most messaged timeslots this academic year.
 */
use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\ServiceAPI\MyRadio_Timeslot;

CoreUtils::getTemplateObject()->setTemplate('table.twig')
    ->addVariable('title', 'Most messaged timeslots this academic year')
    ->addVariable('tabledata', MyRadio_Timeslot::getMostMessaged(strtotime(CoreUtils::getAcademicYear().'-09-01')))
    ->addVariable('tablescript', 'myradio.stats.mostmessagedtimeslot')
    ->render();
