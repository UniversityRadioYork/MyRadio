<?php
require 'Views/MyURY/bootstrap.php';
$twig->setTemplate('MyURY/podcast/podcastimages.twig')
        ->addVariable('title', 'Podcast Manager - Manage Images')
        ->addVariable('heading', 'Podcast Manager')
        ->addVariable('images', $images)
        ->render();