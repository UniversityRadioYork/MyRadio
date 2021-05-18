<?php

use MyRadio\MyRadio\CoreUtils;

CoreUtils::getTemplateObject()
    ->setTemplate('Events/calendar.twig')
    ->render();
