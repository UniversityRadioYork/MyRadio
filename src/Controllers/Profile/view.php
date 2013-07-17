<?php
/**
 * View a User's profile. There are different levels of information available:<br>
 * - Any member can view Name, Sex, College, Officership, Training status and photo of any other member
 * - Any member can also view Phone & email alias of any committee member
 * - Members with AUTH_VIEWOTHERMEMBERS can view eduroam/email/locked/last login/paid of any other member
 * 
 * @author Andy Durant <aj@ury.org.uk>
 * @version 20130717
 * @package MyURY_Profile
 */
// Set if trying to view another member's profile page
if (isset($_GET['memberid'])) {
  $getUserId = $_GET['memberid'];
}

// If trying to view another member, and has permissions to, then load that member
if (isset($getUserId) && $member->hasAuth(AUTH_VIEWOTHERMEMBERS)) {
  $user = User::getInstance($getUserId);
}
// If trying to view another member, and doesn't have permissions to the 403 them
else if (isset($getUserId) && !$member->hasAuth(AUTH_VIEWOTHERMEMBERS)) {
  require 'Controllers/Errors/403.php';
}
// Or just load their own profile
else {
  $user = User::getInstance();
}

// Get the selected users data
$userData = $user->getData();

foreach ($userData['training'] as $k => $v) {
  $userData['training'][$k]['confirmedbyurl'] = CoreUtils::makeURL('Profile', 'view', array('memberid' => $v['confirmedby']));
}

CoreUtils::getTemplateObject()->setTemplate('Profile/user.twig')
        ->addVariable('title', 'View Profile')
        ->addVariable('user', $userData)
        // @todo User.php class needs more to give twig more.
        ->render();