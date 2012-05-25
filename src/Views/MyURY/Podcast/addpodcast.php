<?php
require 'Views/MyURY/bootstrap.php';
$twig->setTemplate('MyURY/Podcast/podcastadd.twig')
        ->addVariable('title', 'Podcast Manager - Add Podcast')
        ->addVariable('heading', 'Podcast Manager')
        ->render();
