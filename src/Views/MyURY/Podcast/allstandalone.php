<?php
require 'Views/MyURY/bootstrap.php';
$twig->setTemplate('MyURY/Podcast/podcast.twig')
        ->addVariable('title', 'Podcast Manager - All Standalone')
        ->addVariable('heading', 'Podcast Manager')
        ->addVariable('standalone', $standalone)
        ->render();
