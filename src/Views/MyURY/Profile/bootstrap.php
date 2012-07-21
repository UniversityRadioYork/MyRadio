<?php
/**
 * 
 * @todo Proper Documentation
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 21072012
 * @package MyURY_Profile
 */
require 'Views/MyURY/bootstrap.php';

//Determine the member name being worked with
if (isset($_GET['memberid'])) {
  $name = User::getInstance((int)$_GET['memberid'])->getName();
} else {
  $name = User::getInstance()->getName();
}

$twig->setTemplate('stripe.twig')
        ->addVariable('title', 'Profiles')
        ->addVariable('profile_name', $name);
