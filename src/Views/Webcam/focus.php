<?php
/**
 * @todo Document
 */
require 'Views/Profile/bootstrap.php';

$twig->setTemplate('Webcam/focus.twig')
        ->addVariable('streams', $streams)
        ->addVariable('live', $live)
        ->render();