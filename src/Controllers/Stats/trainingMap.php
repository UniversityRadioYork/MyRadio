<?php
/**
 * Trainin Map
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130624
 * @package MyURY_Stats
 */
require 'Views/bootstrap.php';

$twig->setTemplate('MyURY/fullimage.twig')
        ->addVariable('title', 'Member Training Graph')
        ->addVariable('caption', 'This screen, updated hourly, provides a complete map of who has trained who. Ever.')
        ->addVariable('image', '/img/stats_training.svg')
        ->render();