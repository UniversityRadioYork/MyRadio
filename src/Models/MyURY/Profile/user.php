<?php
/**
 * @todo Proper Documentation
 * @author Andy Durant <aj@ury.org.uk>
 * @version 26102012
 * @package MyURY_Profile
 */

$getUserId = $_GET['memberid'];

if (isset($getUserId) && $member->hasAuth(204)) {
  $user = User::getInstance($getUserId);
}
else if (isset($getUserId) && !User::hasAuth(204)) {
  require 'Controllers/Errors/403.php';
}
else {
  $user = User::getInstance();
}

$userData = $user->getData();
