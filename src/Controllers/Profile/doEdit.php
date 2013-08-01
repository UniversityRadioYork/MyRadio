<?php
/**
 * Allows Users to edit their profiles, or for admins to edit other users.
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130731
 * @package MyURY_Profile
 */
// Set if trying to view another member's profile page
if (isset($_REQUEST['profileedit-memberid']) && User::getInstance()->hasAuth(AUTH_EDITANYPROFILE)) {
  $user = User::getInstance($_REQUEST['profileedit-memberid']);
} else {
  $user = User::getInstance();
}

$data = $user->getEditForm()->readValues();

$user->setFName($data['fname']);
$user->setSName($data['sname']);
$user->setSex($data['sex']);
$user->setCollegeID($data['collegeid']);
$user->setPhone($data['phone']);
$user->setEmail($data['email']);
$user->setReceiveEmail($data['receive_email']);
$user->setEduroam($data['eduroam']);

if (isset($data['local_name'])) {
  $user->setLocalName($data['local_name']);
  $user->setLocalAlias($data['local_alias']);
}

header('Location: '.CoreUtils::makeURL('Profile', 'view', array('memberid' => $data['memberid'])));