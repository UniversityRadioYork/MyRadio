<?php
/**
 * Main renderer for SIS
 *
 * @author Andy Durant <aj@ury.org.uk>
 * @version 20130923
 * @package MyRadio_SIS
 */

use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\SIS\SIS_Utils;
use \MyRadio\Config;

CoreUtils::requireTimeslot();

$template = 'SIS/main.twig';
$title = 'SIS';

CoreUtils::getTemplateObject()->setTemplate($template)
    ->addVariable('title', $title)
    ->addVariable('modules', Config::$sis_modules)
    ->render();
