<?php
/**
 * Allows Users to edit their profiles, or for admins to edit other users.
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130715
 * @package MyURY_Profile
 */
var_dump($_REQUEST);
// Set if trying to view another member's profile page
if (isset($_REQUEST['profileedit-memberid']) && User::getInstance()->hasAuth(AUTH_EDITANYPROFILE)) {
  $user = User::getInstance($_REQUEST['profileedit-memberid']);
} else {
  $user = User::getInstance();
}

var_dump($user->getEditForm()->readValues());