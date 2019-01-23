<?php
/**
 * The most listened to timeslots this academic year.
 */
use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\ServiceAPI\MyRadio_Show;

CoreUtils::getTemplateObject()->setTemplate('table.twig')
    ->addVariable('title', 'Most listened to shows this academic year')
    ->addVariable('tabledata', MyRadio_Show::getMostListened(strtotime(CoreUtils::getAcademicYear().'-09-01')))
    ->addVariable('tablescript', 'myradio.stats.mostlistenedshow')
    ->render();
