<?php
/**
 * 
 * @todo Proper Documentation
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 21072012
 * @package MyURY_Profile
 */
require 'Views/Profile/bootstrap.php';

$twig->setTemplate('Profile/timeline.twig')
        ->addVariable('title', 'Timeline')
        ->addVariable('timeline', $data)
        ->render();