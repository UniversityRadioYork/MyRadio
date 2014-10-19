<?php
/**
 * List all of the possible permissions available for MyRadio
 *
 * @author Lloyd Wallis
 * @data 20140107
 * @package MyRadio_Core
 */

use \MyRadio\MyRadio\CoreUtils;

$data = array_map(function ($x) {
    $x['usage'] = [
        'display' => 'text',
        'value' => 'Usage',
        'url' => CoreUtils::makeURL('MyRadio', 'permissionUsage', ['typeid' => $x['value']])
    ];
    $x['assigned'] = [
        'display' => 'text',
        'value' => 'Assigned To',
        'url' => CoreUtils::makeURL('MyRadio', 'permissionAssigned', ['typeid' => $x['value']])
    ];

    return $x;
}, CoreUtils::getAllPermissions());

CoreUtils::getTemplateObject()->setTemplate('MyRadio/listPermissions.twig')
        ->addVariable('title', 'Available Permissions')
        ->addVariable('tabledata', $data)
        ->addVariable('tablescript', 'myradio.listPermissions')
        ->render();
