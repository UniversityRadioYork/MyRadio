<?php

use \MyRadio\MyRadio\CoreUtils;

CoreUtils::getTemplateObject()->setTemplate('Quotes/default.twig')
    ->addVariable('title', 'Quotes')
    ->render();
