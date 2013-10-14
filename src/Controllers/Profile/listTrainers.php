<?php
/**
 * List all trainers
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20131014
 * @package MyURY_Profile
 */

$officers = CoreUtils::dataSourceParser(
        MyURY_TrainingStatus::getInstance(3)->getAwardedTo());

CoreUtils::getTemplateObject()->setTemplate('table.twig')
        ->addVariable('tablescript', 'myury.datatable.default')
        ->addVariable('title', 'Trainers List')
        ->addVariable('tabledata', $officers)
        ->render();