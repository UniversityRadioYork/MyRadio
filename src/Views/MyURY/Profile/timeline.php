<?php
require 'Views/MyURY/Profile/bootstrap.php';

$twig->setTemplate('table.twig')
        ->addVariable('tablescript', 'myury.profile.list')
        ->addVariable('heading', 'Members List')
        ->addVariable('tabledata', $data)
        ->render();