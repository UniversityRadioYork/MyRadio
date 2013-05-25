<?php
/**
 * @todo Document
 */
require 'Views/Profile/bootstrap.php';

$twig->setTemplate('Profile/grid.twig')
        ->addVariable('streams', $streams)
        ->render();