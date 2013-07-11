<?php
/**
 * Stats Overview
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130624
 * @package MyURY_Stats
 */
require 'Views/bootstrap.php';

$twig->setTemplate('MyURY/text.twig')
        ->addVariable('title', 'URY Statistics')
        ->addVariable('text', 'This part of MyURY shows you some interesting statistics about the station, from training maps to college breakdowns.')
        ->render();