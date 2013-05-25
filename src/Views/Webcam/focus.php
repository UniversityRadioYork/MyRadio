<?php
/**
 * @todo Document
 */
require 'Views/Profile/bootstrap.php';

$twig->setTemplate('Profile/focus.twig')
        ->addVariable('streams', $streams)
        ->addVariable('live', $live)
        ->render();