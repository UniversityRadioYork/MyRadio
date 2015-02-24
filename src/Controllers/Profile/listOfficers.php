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
    $officers[$k]['editlink'] = [
        'display' => 'icon',
        'value' => 'wrench',
        'title' => 'Edit Officer',
        'url' => CoreUtils::makeURL('Profile', 'editOfficer', ['officerid' => $v['officerid']]),
    ];
}

CoreUtils::getTemplateObject()->setTemplate('table.twig')
    ->addVariable('tablescript', 'myury.profile.listOfficers')
    ->addVariable('title', 'Officers List')
    ->addVariable('tabledata', $officers)
    ->render();
