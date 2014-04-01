<?php
CoreUtils::getTemplateObject()->setTemplate('Webcam/grid.twig')
    ->addVariable('streams', $streams)
    ->render();
