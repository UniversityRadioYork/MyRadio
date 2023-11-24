<?php

use MyRadio\MyRadio\CoreUtils;

$kitLists = file_get_contents("http://evergiven.ury.york.ac.uk:8093/");

CoreUtils::getTemplateObject()->setTemplate('Events/kitLists.twig')
    ->addVariable('kitLists', $kitLists)
    ->render();
