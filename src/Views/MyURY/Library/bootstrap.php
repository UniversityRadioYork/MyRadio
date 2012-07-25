<?php
require 'Views/MyURY/bootstrap.php';
$twig->setTemplate('stripe.twig')
	->addVariable('title', 'Library')
        ->addVariable('submenu', (new MyURYMenu())->getSubMenuForUser(9, $member));
$twig->addInfo('This is a library!');