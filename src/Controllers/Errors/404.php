<?php
/**
 * If you don't know what a 404 page is, you have a long way to go.
 */
use \MyRadio\MyRadio\CoreUtils;

$twig = CoreUtils::getTemplateObject();
require 'Views/Errors/404.php';
