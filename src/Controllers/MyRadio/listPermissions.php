<?php
/**
 * List all of the possible permissions available for MyRadio.
 *
 * @data    20140107
 */
use \MyRadio\MyRadio\AuthUtils;
use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\MyRadio\URLUtils;

$data = array_map(
    function ($x) {
        $x['usage'] = [
        'display' => 'text',
        'value' => 'Usage',
        'url' => URLUtils::makeURL('MyRadio', 'permissionUsage', ['typeid' => $x['value']]),
        ];
        $x['assigned'] = [
        'display' => 'text',
        'value' => 'Assigned To',
        'url' => URLUtils::makeURL('MyRadio', 'permissionAssignedTo', ['typeid' => $x['value']]),
        ];

        return $x;
    },
    AuthUtils::getAllPermissions()
);

CoreUtils::getTemplateObject()->setTemplate('MyRadio/listPermissions.twig')
        ->addVariable('title', 'Permissions')
        ->addVariable('subtitle', 'Available Permissions')
        ->addVariable('tabledata', $data)
        ->addVariable('tablescript', 'myradio.listPermissions')
        ->render();
