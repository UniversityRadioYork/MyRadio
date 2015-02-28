<?php
/**
 * Stats Overview
 *
 * @package MyRadio_Stats
 */

use \MyRadio\MyRadio\CoreUtils;

CoreUtils::getTemplateObject()->setTemplate('MyRadio/text.twig')
    ->addVariable('title', 'Statistics')
    ->addVariable(
        'text',
        'This part of MyRadio shows you some interesting statistics about the station, '
        .'from training maps to college breakdowns.'
    )->render();
