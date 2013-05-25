<?php
/**
 * 
 * @todo Proper Documentation
 * @author Andy Durant <aj@ury.org.uk>
 * @version 20130525
 * @package MyURY_Podcast
 */
require 'Views/bootstrap.php';
$twig->setTemplate('Podcast/podcastadd.twig')
        ->addVariable('title', 'Podcast Manager - Add Podcast')
        ->render();
