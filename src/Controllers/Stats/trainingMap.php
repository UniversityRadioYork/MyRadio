<?php
/**
 * Trainin Map
 *
 * @package MyRadio_Stats
 */

use \MyRadio\MyRadio\CoreUtils;

CoreUtils::getTemplateObject()->setTemplate('MyRadio/fullimage.twig')
    ->addVariable('title', 'Member Training Graph')
    ->addVariable('caption', 'This screen, updated hourly, provides a complete map of who has trained who. Ever.')
    ->addVariable('image', '/img/stats_training.svg')
    ->render();
