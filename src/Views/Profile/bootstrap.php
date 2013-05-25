<?php
/**
 * 
 * @todo Proper Documentation
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130525
 * @package MyURY_Profile
 */
require 'Views/bootstrap.php';

//Determine the member name being worked with
if (isset($_GET['memberid'])) {
  $name = User::getInstance((int)$_GET['memberid'])->getName();
} else {
  $name = User::getInstance()->getName();
}

$twig->addVariable('profile_name', $name);
