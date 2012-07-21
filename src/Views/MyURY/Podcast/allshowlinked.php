<?php
/**
 * 
 * @todo Proper Documentation
 * @author Andy Durant <aj@ury.org.uk>
 * @version 21072012
 * @package MyURY_Podcast
 */
require 'Views/MyURY/bootstrap.php';
$twig->setTemplate('MyURY/Podcast/podcast.twig')
        ->addVariable('title', 'Podcast Manager - All Show Linked')
        ->addVariable('heading', 'Podcast Manager')
        ->addVariable('showlinked', $showlinked)
        ->render();
