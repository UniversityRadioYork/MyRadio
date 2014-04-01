<?php
CoreUtils::getTemplateObject()->setTemplate('Webcam/focus.twig')
    ->addVariable('streams', $streams)
    ->addVariable('live', $live)
    ->render();
