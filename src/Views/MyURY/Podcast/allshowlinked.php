<?php
require 'Views/MyURY/bootstrap.php';
$twig->setTemplate('MyURY/Podcast/podcast.twig')
        ->addVariable('title', 'Podcast Manager - All Show Linked')
        ->addVariable('heading', 'Podcast Manager')
        ->addVariable('showlinked', $showlinked)
        ->render();
