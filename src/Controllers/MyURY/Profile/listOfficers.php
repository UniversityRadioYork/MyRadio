<?php
/**
 * 
 * @todo Proper Documentation
 * @todo Permissions
 * @author Andy Durant <aj@ury.org.uk>
 * @version 20130516
 * @package MyURY_Profile
 */

$officers = Profile::getCurrentOfficers();
//require 'Views/MyURY/Profile/listOfficers.php';

$twig->setTemplate('table.twig')
        ->addVariable('tablescript', 'myury.profile.listOfficers')
        ->addVariable('title', 'Officers List')
        ->addVariable('tabledata', $officers)
        ->render();