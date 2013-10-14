<?php
/**
 * List all trainers
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20131014
 * @package MyURY_Profile
 */

$officers = User::findAllTrainers();

foreach ($officers as $k => $v) {
  if (!empty($officers[$k]['name'])) {
          $officers[$k]['name'] = array(
              'display' => 'text',
              'url' => CoreUtils::makeURL('Profile', 'view', array('memberid' => $v['memberid'])),
              'value' => $v['name']
              );
        }
}

CoreUtils::getTemplateObject()->setTemplate('table.twig')
        ->addVariable('tablescript', 'myury.profile.listOfficers')
        ->addVariable('title', 'Trainers List')
        ->addVariable('tabledata', $officers)
        ->render();