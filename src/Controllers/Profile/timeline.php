<?php
/**
 * @todo Proper Documentation
 * @todo Permissions
 */
use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\ServiceAPI\MyRadio_User;

$user = MyRadio_User::getInstance(isset($_GET['memberid']) ? $_GET['memberid'] : $_SESSION['memberid']);
$data = $user->getTimeline();

CoreUtils::getTemplateObject()->setTemplate('Profile/timeline.twig')
    ->addVariable('title', 'Timeline')
    ->addVariable('timeline', $data)
    ->addVariable('profile_name', $user->getName())
    ->render();
