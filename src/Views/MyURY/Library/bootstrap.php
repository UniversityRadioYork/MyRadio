<?php
require 'Views/MyURY/bootstrap.php';
$twig->setTemplate('stripe.twig')
	->addVariable('title', 'Library');
$twig->addInfo('This is a library!');