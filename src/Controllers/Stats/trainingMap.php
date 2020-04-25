<?php
/**
 * Training Map.
 */
use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\ServiceAPI\MyRadio_TrainingStatus;

$status_map = function ($status) {
    return ['value' => $status->getID(), 'text' => $status->getTitle()];
};

$caption = 'Please select a Training Status above.';

if (isset($_GET['id'])) {
    $title = MyRadio_TrainingStatus::getInstance($_GET['id'])->getTitle();
    $caption = 'List of the people who have: ' . $title . '...';
    $text = implode(" ,  ",MyRadio_TrainingStatus::getInstance($_GET['id'])->getListAll());
}

CoreUtils::getTemplateObject()->setTemplate('MyRadio/trainingMap.twig')
    ->addVariable('title', 'Member Training Graph')
    ->addVariable('maps', array_map($status_map, MyRadio_TrainingStatus::getAll()))
    ->addVariable('caption', $caption)
    ->addVariable('text', $text)
    ->render();
