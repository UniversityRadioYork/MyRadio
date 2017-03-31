<?php
/**
 * Provides the import popup page for the Show Planner Importer feature.
 */
use \MyRadio\MyRadio\AuthUtils;
use \MyRadio\MyRadio\CoreUtils;

$template = 'NIPSWeb/import.twig';

CoreUtils::getTemplateObject()->setTemplate($template)
    ->render();
