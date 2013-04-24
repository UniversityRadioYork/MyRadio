<?php
/**
 * @todo Document
 */
require 'Views/MyURY/Webcam/bootstrap.php';

$twig->setTemplate('MyURY/Webcam/grid.twig')
        ->addVariable('streams', $streams)
        ->render();