<?php
require 'Views/MyURY/bootstrap.php';
$twig->setTemplate('MyURY/podcast/podcast.twig')
        ->addVariable('title', 'Podcast Manager - All Standalone')
        ->addVariable('heading', 'Podcast Manager')
        ->addVariable('standalone', $standalone)
        ->render();
