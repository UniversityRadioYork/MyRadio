<?php
/**
 * Main renderer for SIS
 *
 * @author  Andy Durant <aj@ury.org.uk>
 * @version 20130923
 * @package MyRadio_SIS
 */

use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\SIS\SIS_Utils;

CoreUtils::requireTimeslot();

$template = 'SIS/main.twig';
$title = 'SIS';
$plugins = SIS_Utils::getPlugins();
$tabs = SIS_Utils::getTabs();

CoreUtils::getTemplateObject()->setTemplate($template)
    ->addVariable('title', $title)
    ->addVariable('plugins', $plugins)
    ->addVariable('tabs', $tabs)
    ->render();
