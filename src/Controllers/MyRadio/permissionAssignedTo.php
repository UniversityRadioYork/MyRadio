<?php
/**
 * List all of the possible permissions available for MyRadio.
 *
 * @data    20140107
 */
use \MyRadio\MyRadio\AuthUtils;
use \MyRadio\MyRadio\CoreUtils;

$assignedTo = AuthUtils::getPermissionAssignedTo($_REQUEST['typeid']);

CoreUtils::getTemplateObject()->setTemplate('MyRadio/permissionAssignedTo.twig')
        ->addVariable('title', 'Permissions')
        ->addVariable('subtitle', 'Users Assigned "'.AuthUtils::getAuthDescription($_REQUEST['typeid']).'"')
        ->addVariable('assignedTo', $assignedTo)
        ->render();
