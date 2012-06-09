<?php
require 'Views/MyURY/Scheduler/bootstrap.php';
$twig->setTemplate('menu.twig')
        ->addVariable('heading', 'Scheduler')
        ->addVariable('menu', array($items))
        ->render();