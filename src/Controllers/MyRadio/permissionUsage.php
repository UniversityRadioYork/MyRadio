<?php
/**
 * List all of the possible permissions available for MyRadio
 *
 * @author Lloyd Wallis
 * @data 20140107
 * @package MyRadio_Core
 */

use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\MyRadio\AuthUtils;

$usage = AuthUtils::getAuthUsage($_REQUEST['typeid']);

CoreUtils::getTemplateObject()->setTemplate('MyRadio/permissionUsage.twig')
        ->addVariable('title', 'Permission Usage | '.AuthUtils::getAuthDescription($_REQUEST['typeid']))
        ->addVariable('usage', $usage)
        ->render();
