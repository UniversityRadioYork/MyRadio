<?php
/**
 * The most listened to timeslots this academic year
 *
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130626
 * @package MyRadio_Stats
 */
CoreUtils::getTemplateObject()->setTemplate('table.twig')
    ->addVariable('title', 'Most listened to timeslots this academic year')
    ->addVariable('tabledata', MyRadio_Timeslot::getMostListened(strtotime(CoreUtils::getAcademicYear().'-09-01')))
    ->addVariable('tablescript', 'myury.stats.mostlistenedtimeslot')
    ->render();
