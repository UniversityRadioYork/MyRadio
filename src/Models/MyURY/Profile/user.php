<?php
/**
 * @todo Proper Documentation
 * @author Andy Durant <aj@ury.org.uk>
 * @version 26102012
 * @package MyURY_Profile
 */

$memberid = $_GET['memberid'];

if (isset($memberid) && User::hasAuth(204)) {
  $user = User::getInstance($memberid);
}
else if (isset($memberid) && !User::hasAuth(204)) {
  require 'Controllers/Errors/403.php';
}
else {
  $user = User::getInstance();
}

$userData = $user->getData();
