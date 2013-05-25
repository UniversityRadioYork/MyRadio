<?php
/**
 * @todo Document
 */
require 'Views/Webcam/bootstrap.php';

$twig->setTemplate('Webcam/grid.twig')
        ->addVariable('streams', $streams)
        ->render();