<?php
/**
 * @todo Document
 */

$twig->setTemplate('MyURY/Webcam/grid.twig')
        ->addVariable('streams', $streams)
        ->render();