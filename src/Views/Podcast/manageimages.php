<?php
/**
 * 
 * @todo Proper Documentation
 * @author Andy Durant <aj@ury.org.uk>
 * @version 20130525
 * @package MyURY_Podcast
 */
require 'Views/bootstrap.php';
$twig->setTemplate('Podcast/podcastimages.twig')
        ->addVariable('title', 'Podcast Manager - Manage Images')
        ->addVariable('images', $images)
        ->render();