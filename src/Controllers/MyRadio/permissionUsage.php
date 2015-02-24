<?php
/**
 * List all of the possible permissions available for MyRadio
 *
 * @data    20140107
 * @package MyRadio_Core
 */

use \MyRadio\MyRadio\CoreUtils;

$usage = CoreUtils::getAuthUsage($_REQUEST['typeid']);

CoreUtils::getTemplateObject()->setTemplate('MyRadio/permissionUsage.twig')
        ->addVariable('title', 'Permission Usage | '.CoreUtils::getAuthDescription($_REQUEST['typeid']))
        ->addVariable('usage', $usage)
        ->render();
