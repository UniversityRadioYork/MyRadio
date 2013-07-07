<?php
/**
 * The most messaged timeslots this academic year
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130626
 * @package MyURY_Stats
 */
require 'Views/bootstrap.php';

$twig->setTemplate('table.twig')
        ->addVariable('title', 'Most messaged timeslots this academic year')
        ->addVariable('heading', 'Most messaged timeslots this academic year')
        ->addVariable('tabledata', MyURY_Timeslot::getMostMessaged(strtotime(CoreUtils::getAcademicYear().'-09-01')))
        ->addVariable('tablescript', 'myury.stats.mostmessagedtimeslot')
        ->render();