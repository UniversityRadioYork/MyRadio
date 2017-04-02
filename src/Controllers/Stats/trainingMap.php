<?php
/**
 * Training Map.
 */
use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\ServiceAPI\MyRadio_TrainingStatus;

function status_map($status)
{
  return [
    'value' => $status->getID(),
    'text' => $status->getTitle()
  ];
}

$caption = 'Please select a Training Status above.';
if (isset($_GET['id'])) {
  $title = MyRadio_TrainingStatus::getInstance($_GET['id'])->getTitle();
  $caption = 'This is a map of who trained who for the ' . $title . ' Training Status.';
}

CoreUtils::getTemplateObject()->setTemplate('MyRadio/trainingMap.twig')
    ->addVariable('title', 'Member Training Graph')
    ->addVariable('maps', array_map('status_map', MyRadio_TrainingStatus::getAll()))
    ->addVariable('caption', $caption)
    ->addVariable('image', 'img/stats_training_' . $_GET['id'] . '.svg')
    ->render();
