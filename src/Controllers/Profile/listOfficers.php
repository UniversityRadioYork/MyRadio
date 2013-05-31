<?php
/**
 * 
 * @todo Proper Documentation
 * @todo Permissions
 * @author Andy Durant <aj@ury.org.uk>
 * @version 20130516
 * @package MyURY_Profile
 */

$officers = Profile::getCurrentOfficers();

require 'Views/Profile/bootstrap.php';

foreach ($officers as $k => $v) {
  if (!empty($officers[$k]['name'])) {
	  $officers[$k]['name'] = array(
	      'display' => 'text',
	      'url' => CoreUtils::makeURL('Profile', 'view', array('memberid' => $v['memberid'])),
	      'value' => $v['name']
	      );
	}
	$officers[$k]['editlink'] = array(
		'display' => 'icon',
        'value' => 'wrench',
        'title' => 'Edit Officer',
        'url' => CoreUtils::makeURL('Profile', 'editOfficer', array('officerid' => $v['officerid'])),
        );
}

$twig->setTemplate('table.twig')
        ->addVariable('tablescript', 'myury.profile.listOfficers')
        ->addVariable('title', 'Officers List')
        ->addVariable('tabledata', $officers)
        ->render();