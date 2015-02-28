<?php
/**
 *
 * @todo Proper Documentation
 * @todo Permissions
 * @package MyRadio_Profile
 */

use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\ServiceAPI\Profile;

$officers = Profile::getOfficers();

foreach ($officers as $k => $v) {
    if (!empty($officers[$k]['name'])) {
        $officers[$k]['name'] = [
            'display' => 'text',
            'url' => CoreUtils::makeURL('Profile', 'view', ['memberid' => $v['memberid']]),
            'value' => $v['name']
        ];
    }
    $officers[$k]['viewlink'] = [
        'display' => 'icon',
        'value' => 'user',
        'title' => 'View Officer',
        'url' => CoreUtils::makeURL('Profile', 'officer', ['officerid' => $v['officerid']]),
    ];
    $officers[$k]['editlink'] = [
        'display' => 'icon',
        'value' => 'pencil',
        'title' => 'Edit Officer',
        'url' => CoreUtils::makeURL('Profile', 'editOfficer', ['officerid' => $v['officerid']]),
    ];
    $officers[$k]['assignlink'] = [
        'display' => 'icon',
        'value' => 'plus',
        'title' => 'Assign Officer',
        'url' => CoreUtils::makeURL('Profile', 'assignOfficer', ['officerid' => $v['officerid']]),
    ];
}

CoreUtils::getTemplateObject()->setTemplate('table.twig')
    ->addVariable('tablescript', 'myradio.profile.listOfficers')
    ->addVariable('title', 'Officers List')
    ->addVariable('tabledata', $officers)
    ->render();
