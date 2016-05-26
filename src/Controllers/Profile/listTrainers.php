<?php
/**
 * List all trainers.
 */
use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\ServiceAPI\MyRadio_TrainingStatus;

$trainers = CoreUtils::dataSourceParser(
    MyRadio_TrainingStatus::getInstance(3)->getAwardedTo()
);

foreach ($trainers as $key => $value) {
    $trainers[$key]['awarded_time'] = CoreUtils::happyTime($trainers[$key]['awarded_time'], false);
}

CoreUtils::getTemplateObject()->setTemplate('table.twig')
    ->addVariable('tablescript', 'myradio.profile.listTrainers')
    ->addVariable('title', 'Trainers List')
    ->addVariable('tabledata', $trainers)
    ->render();
