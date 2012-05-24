<?php
require 'Views/MyURY/bootstrap.php';
$twig->setTemplate('MyURY/podcast/podcast.twig')
        ->addVariable('title', 'Podcast Manager')
        ->addVariable('heading', 'Podcast Manager')
        ->addVariable('standalone', $standalone)
        ->addVariable('showlinked', $showlinked)
        ->render();
