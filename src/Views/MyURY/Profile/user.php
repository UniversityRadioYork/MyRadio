<?php
/**
 * 
 * @todo Proper Documentation
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 21072012
 * @package MyURY_Profile
 */
require 'Views/MyURY/Profile/bootstrap.php';

$twig->setTemplate('MyURY/Profile/user.twig')
        ->addVariable('title', 'View Member')
        ->addVariable('title', 'View Profile')
        ->addVariable('user', $userData)
        // @todo User.php class needs more to give twig more.
        ->render();