<?php
/**
 * The most listened to timeslots this academic year
 *
 * @package MyRadio_Stats
 */

use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\ServiceAPI\MyRadio_Show;

CoreUtils::getTemplateObject()->setTemplate('table.twig')
    ->addVariable('title', 'Most listened to shows this academic year')
    ->addVariable('tabledata', MyRadio_Show::getMostListened(strtotime(CoreUtils::getAcademicYear().'-09-01')))
    ->addVariable('tablescript', 'myury.datatable.default')
    ->render();
