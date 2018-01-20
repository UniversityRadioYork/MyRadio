<?php
/**
 * List all of the possible permissions available for MyRadio.
 *
 * @data    20140107
 */
use \MyRadio\MyRadio\AuthUtils;
use \MyRadio\MyRadio\CoreUtils;

$usage = AuthUtils::getAuthUsage($_REQUEST['typeid']);

CoreUtils::getTemplateObject()->setTemplate('MyRadio/permissionUsage.twig')
        ->addVariable('title', 'Permissions')
        ->addVariable('subtitle', '"'.AuthUtils::getAuthDescription($_REQUEST['typeid']).'" Usage')
        ->addVariable('usage', $usage)
        ->render();
