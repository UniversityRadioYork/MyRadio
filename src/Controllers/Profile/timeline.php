<?php
/**
 *
 * @todo Proper Documentation
 * @todo Permissions
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130803
 * @package MyRadio_Profile
 */
$user = MyRadio_User::getInstance(isset($_GET['memberid']) ? $_GET['memberid'] : $_SESSION['memberid']);
$data = $user->getTimeline();

CoreUtils::getTemplateObject()->setTemplate('Profile/timeline.twig')
    ->addVariable('title', 'Timeline')
    ->addVariable('timeline', $data)
    ->addVariable('profile_name', $user->getName())
    ->render();
