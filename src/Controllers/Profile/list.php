<?php
/**
 *
 * @todo Proper Documentation
 * @todo Permissions
 * @author Andy Durant <aj@ury.org.uk>
 * @version 20130516
 * @package MyRadio_Profile
 */

$members = Profile::getThisYearsMembers();

foreach ($members as $k => $v) {
    $members[$k]['name'] = array(
        'display' => 'text',
        'url' => CoreUtils::makeURL('Profile', 'view', array('memberid' => $v['memberid'])),
        'value' => $v['name']
    );
}

CoreUtils::getTemplateObject()->setTemplate('table.twig')
    ->addVariable('tablescript', 'myury.profile.list')
    ->addVariable('title', 'Members List')
    ->addVariable('tabledata', $members)
    ->render();
