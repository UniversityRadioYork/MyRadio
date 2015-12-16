<?php
/**
 * If you don't know what a 403 page is, you have a long way to go.
 */
use \MyRadio\MyRadio\CoreUtils;

$twig = CoreUtils::getTemplateObject();
require 'Views/Errors/403.php';
