<?php
require 'Views/MyURY/Profile/bootstrap.php';

$twig->setTemplate('MyURY/Profile/timeline.twig')
        ->addVariable('title', 'Timeline')
        ->addVariable('timeline', $data)
        ->render();