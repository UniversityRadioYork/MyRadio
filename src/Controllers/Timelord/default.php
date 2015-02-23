<?php
/**
 * Main renderer for Timelord
 *
 * @package MyRadio_Timelord
 */

use \MyRadio\MyRadio\CoreUtils;

CoreUtils::getTemplateObject()->setTemplate('Timelord/main.twig')
    ->addVariable('title', 'Studio Clock')
    ->render();
