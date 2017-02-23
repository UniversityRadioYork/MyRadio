<?php
/**
 * The most listened to timeslots this academic year.
 */
use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\ServiceAPI\MyRadio_Timeslot;

CoreUtils::getTemplateObject()->setTemplate('table.twig')
    ->addVariable('title', 'Most listened to timeslots this academic year')
    ->addVariable('tabledata', MyRadio_Timeslot::getMostListened(strtotime(CoreUtils::getAcademicYear().'-09-01')))
    ->addVariable('tablescript', 'myradio.stats.mostlistenedtimeslot')
    ->render();
