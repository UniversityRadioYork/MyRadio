<?php
/**
 * List all trainers
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20131014
 * @package MyURY_Profile
 */

$officers = CoreUtils::dataSourceParser(User::findAllTrainers(),false);

CoreUtils::getTemplateObject()->setTemplate('table.twig')
        ->addVariable('tablescript', 'myury.profile.listTrainers')
        ->addVariable('title', 'Trainers List')
        ->addVariable('tabledata', $officers)
        ->render();