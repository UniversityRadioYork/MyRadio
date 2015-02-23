<?php
/**
 *
 * @todo Proper Documentation
 * @todo Permissions
 * @package MyRadio_Profile
 */

use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\ServiceAPI\Profile;

$members = Profile::getThisYearsMembers();

foreach ($members as $k => $v) {
    $members[$k]['name'] = [
        'display' => 'text',
        'url' => CoreUtils::makeURL('Profile', 'view', ['memberid' => $v['memberid']]),
        'value' => $v['name']
    ];
}

CoreUtils::getTemplateObject()->setTemplate('table.twig')
    ->addVariable('tablescript', 'myury.profile.list')
    ->addVariable('title', 'Members List')
    ->addVariable('tabledata', $members)
    ->render();
