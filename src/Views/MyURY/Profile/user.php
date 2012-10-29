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
        ->addVariable('heading', 'View Profile')
        ->addVariable('user', $userData)
        // $name is set elsewhere! (for the header)
        ->addVariable('name', $name)
        // @todo User.php class needs more to give twig more.
        ->render();