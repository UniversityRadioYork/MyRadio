<?php
/**
 * @todo Document
 */
require 'Views/MyURY/Webcam/bootstrap.php';

$twig->setTemplate('MyURY/Webcam/focus.twig')
        ->addVariable('streams', $streams)
        ->addVariable('live', $live)
        ->render();