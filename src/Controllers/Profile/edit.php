<?php
/**
 * Allows Users to edit their profiles, or for admins to edit other users.
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130715
 * @package MyRadio_Profile
 */

// Set if trying to view another member's profile page
if (isset($_REQUEST['memberid']) && User::getInstance()->hasAuth(AUTH_EDITANYPROFILE)) {
  $user = User::getInstance($_REQUEST['memberid']);
} else {
  $user = User::getInstance();
}

$user->getEditForm()->render();