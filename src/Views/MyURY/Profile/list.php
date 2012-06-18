<?php
require 'Views/MyURY/Profile/bootstrap.php';

foreach ($members as $k => $v) {
  $members[$k]['fname'] = array(
      'display' => 'text',
      'url' => CoreUtils::makeURL('Profile', 'view', array('memberid' => $v['memberid'])),
      'value' => $v['fname']
      );
}

$twig->setTemplate('table.twig')
        ->addVariable('tablescript', 'myury.profile.list')
        ->addVariable('heading', 'Members List')
        ->addVariable('tabledata', $members)
        ->render();