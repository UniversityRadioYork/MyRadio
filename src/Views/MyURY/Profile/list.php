<?php
require 'Views/MyURY/Profile/bootstrap.php';

foreach ($members as $k => $v) {
  $members[$k]['name'] = array(
      'display' => 'text',
      'url' => CoreUtils::makeURL('Profile', 'view', array('memberid' => $v['memberid'])),
      'value' => $v['name']
      );
}

$twig->setTemplate('table.twig')
        ->addVariable('tablescript', 'myury.profile.list')
        ->addVariable('heading', 'Members List')
        ->addVariable('tabledata', $members)
        ->render();