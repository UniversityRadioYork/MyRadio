<?php
require 'Views/MyURY/Profile/bootstrap.php';

$twig->setTemplate('MyURY/Profile/timeline.twig')
        ->addVariable('heading', 'Timeline')
        ->addVariable('timeline', $members)
        ->render();