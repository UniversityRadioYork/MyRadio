<?php
/**
 * List all trainers
 *
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20131014
 * @package MyRadio_Profile
 */

$officers = CoreUtils::dataSourceParser(
    MyRadio_TrainingStatus::getInstance(3)->getAwardedTo()
);

foreach ($officers as $key => $value) {
    $officers[$key]['awarded_time'] = date('Y/m/d', $officers[$key]['awarded_time']);
}

CoreUtils::getTemplateObject()->setTemplate('table.twig')
    ->addVariable('tablescript', 'myradio.profile.listTrainers')
    ->addVariable('title', 'Trainers List')
    ->addVariable('tabledata', $officers)
    ->render();
