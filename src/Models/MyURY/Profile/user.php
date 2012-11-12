<?php
/**
 * @todo Proper Documentation
 * @author Andy Durant <aj@ury.org.uk>
 * @version 26102012
 * @package MyURY_Profile
 */

// Set if trying to view another member's profile page
$getUserId = $_GET['uid'];

// If trying to view another member, and has permissions to, then load that member
if (isset($getUserId) && $member->hasAuth(204)) {
  $user = User::getInstance($getUserId);
}
// If trying to view another member, and doesn't have permissions to the 403 them
else if (isset($getUserId) && !$member->hasAuth(204)) {
  require 'Controllers/Errors/403.php';
}
// Or just load their own profile
else {
  $user = User::getInstance();
}

// Get the selected users data
$userData = $user->getData();