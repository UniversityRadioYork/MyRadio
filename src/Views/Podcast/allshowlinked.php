<?php
/**
 * 
 * @todo Proper Documentation
 * @author Andy Durant <aj@ury.org.uk>
 * @version 20130525
 * @package MyURY_Podcast
 */
require 'Views/bootstrap.php';
$twig->setTemplate('Podcast/podcast.twig')
        ->addVariable('title', 'Podcast Manager - All Show Linked')
        ->addVariable('showlinked', $showlinked)
        ->render();
