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
$img = '';
if (isset($_GET['id'])) {
    $title = MyRadio_TrainingStatus::getInstance($_GET['id'])->getTitle();
    $caption = 'This is a map of who trained who for the ' . $title . ' Training Status.';
    $img = 'img/stats_training_' . $_GET['id'] . '.svg';
}

CoreUtils::getTemplateObject()->setTemplate('MyRadio/trainingMap.twig')
    ->addVariable('title', 'Member Training Graph')
    ->addVariable('maps', array_map($status_map, MyRadio_TrainingStatus::getAll()))
    ->addVariable('caption', $caption)
    ->addVariable('image', $img)
    ->render();
