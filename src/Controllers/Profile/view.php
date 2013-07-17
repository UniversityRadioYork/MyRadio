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
$user = User::getInstance(empty($_REQUEST['memberid']) ? -1 : $_REQUEST['memberid']);
echo nl2br(print_r($user,true));
//Add global user data
$userData = array(
    'fname' => $user->getFName(),
    'sname' => $user->getSName(),
    'sex' => $user->getSex(),
    'college' => $user->getCollege(),
    'officerships' => $user->getOfficerships(),
    'training' => $user->getTraining(),
    'photo' => $user->getProfilePhoto() === null ? Config::$default_person_uri : $user->getProfilePhoto()->getURL()
);

if ($user->isOfficer()) {
  $userData['phone'] = $user->getPhone();
  $userData['email'] = $user->getPublicEmail();
}

if (User::getInstance()->hasAuth(AUTH_VIEWOTHERMEMBERS)) {
  $userData['email'] = $user->getEmail();
  $userData['eduroam'] = $user->getEduroam();
  $userData['local_alias'] = $user->getLocalAlias();
  $userData['local_name'] = $user->getLocalName();
  $userData['account_locked'] = $user->getAccountLocked();
  $userData['last_login'] = $user->getLastLogin();
  $userData['payment'] = $user->getAllPayments();
}

foreach ($userData['training'] as $k => $v) {
  $userData['training'][$k]['confirmedbyurl'] = CoreUtils::makeURL('Profile', 'view', array('memberid' => $v['confirmedby']));
}

CoreUtils::getTemplateObject()->setTemplate('Profile/user.twig')
        ->addVariable('title', 'View Profile')
        ->addVariable('user', $userData)
        ->render();