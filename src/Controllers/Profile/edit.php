<?php
/**
 * Allows Users to edit their profiles, or for admins to edit other users.
 *
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130715
 * @package MyRadio_Profile
 */

// Set if trying to view another member's profile page
if (isset($_REQUEST['memberid']) && MyRadio_User::getInstance()->hasAuth(AUTH_EDITANYPROFILE)) {
    $user = MyRadio_User::getInstance($_REQUEST['memberid']);
} else {
    $user = MyRadio_User::getInstance();
}

$user->getEditForm()->render();
