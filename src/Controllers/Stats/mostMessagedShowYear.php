<?php
/**
 * The most messaged shows this academic year
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130626
 * @package MyURY_Stats
 */
require 'Views/bootstrap.php';

CoreUtils::requireTimeslot();

$twig->setTemplate('MyURY/table.twig')
        ->addVariable('title', 'Most messaged shows this academic year')
        ->addVariable('heading', 'Most messaged shows this academic year')
        ->addVariable('data', MyURY_Show::getMostMessaged(strtotime(CoreUtils::getAcademicYear().'-09-01')))
        ->render();