<?php
/**
 * Main renderer for NIPSWeb.
 */
use \MyRadio\MyRadio\AuthUtils;
use \MyRadio\MyRadio\CoreUtils;

$template = 'NIPSWeb/import.twig';

CoreUtils::getTemplateObject()->setTemplate($template)
    ->render();
