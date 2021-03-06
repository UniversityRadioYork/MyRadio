<?php
/**
 * Main renderer for SIS.
 */
use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\SIS\SIS_Utils;

CoreUtils::requireTimeslot();

$template = 'SIS/main.twig';
$title = 'SIS';

CoreUtils::getTemplateObject()->setTemplate($template)
    ->addVariable('title', $title)
    ->addVariable('modules', SIS_Utils::getModulesForUser())
    ->render();
