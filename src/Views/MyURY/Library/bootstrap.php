<?php
require 'Views/MyURY/bootstrap.php';
$twig->setTemplate('stripe.twig')
	->addVariable('title', 'Library')
        /**
         * @todo Some kind of getModuleID($modulename) method in coreutils for make this better?
         */
        ->addVariable('submenu', (new MyURYMenu())->getSubMenuForUser(9, $member));
$twig->addInfo('This is a library!');