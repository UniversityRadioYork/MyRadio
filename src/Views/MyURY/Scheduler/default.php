<?php
require 'Views/MyURY/Scheduler/bootstrap.php';
$twig->setTemplate('MyURY/menu.twig')
        ->addVariable('heading', 'Scheduler')
        ->addVariable('menu', array($items))
        ->render();