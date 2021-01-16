<?php
/**
 * Provides the playout popup page for the automatic playout feature
 */

use \MyRadio\MyRadio\CoreUtils;

$template = 'NIPSWeb/playout.twig';

CoreUtils::getTemplateObject()->setTemplate($template)
    ->render();
