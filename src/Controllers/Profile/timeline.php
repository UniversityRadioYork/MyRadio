<?php
/**
 * 
 * @todo Proper Documentation
 * @todo Permissions
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 21072012
 * @package MyURY_Profile
 */
$user = User::getInstance(isset($_GET['memberid']) ? $_GET['memberid'] : $_SESSION['memberid']);
$data = $user->getTimeline();

CoreUtils::getTemplateObject()->setTemplate('Profile/timeline.twig')
        ->addVariable('title', 'Timeline')
        ->addVariable('timeline', $data)
        ->addVariable('profile_name', $user->getName())
        ->render();