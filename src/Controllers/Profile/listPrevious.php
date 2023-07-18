<?php
/**
 * Returns a list of all members who were active in the previous academic year.
 *
 * @todo Use Users better
 */
use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\MyRadio\URLUtils;
use \MyRadio\ServiceAPI\Profile;

$members = Profile::getMembersForYear(CoreUtils::getAcademicYear() - 1);

foreach ($members as $k => $v) {
    $members[$k]['name'] = [
        'display' => 'text',
        'url' => URLUtils::makeURL('Profile', 'view', ['memberid' => $v['memberid']]),
        'value' => $v['name'],
    ];
    unset($members[$k]['email']);
    unset($members[$k]['eduroam']);
}

CoreUtils::getTemplateObject()->setTemplate('table.twig')
    ->addVariable('tablescript', 'myradio.profile.list')
    ->addVariable('title', 'Last Year\'s Members List')
    ->addVariable('tabledata', $members)
    ->render();
