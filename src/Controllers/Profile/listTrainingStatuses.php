<?php
/**
 * List all training statuses.
 */
use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\ServiceAPI\MyRadio_TrainingStatus;

$trainingStatuses = MyRadio_TrainingStatus::getAll();

CoreUtils::getTemplateObject()->setTemplate('table.twig')
    ->addVariable('tablescript', 'myradio.profile.listTrainingStatuses')
    ->addVariable('title', 'Training Statuses')
    ->addVariable('tabledata', $trainingStatuses)
    ->render();
