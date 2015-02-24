<?php
/**
 * Landing page for iTones
 *
 * @package MyRadio_iTones
 */

use \MyRadio\MyRadio\CoreUtils;

CoreUtils::getTemplateObject()
    ->setTemplate('iTones/default.twig')
    ->addVariable('title', 'Campus Jukebox Manager')
    ->render();
