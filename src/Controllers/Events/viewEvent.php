<?php

use MyRadio\MyRadio\CoreUtils;
use MyRadio\ServiceAPI\MyRadio_Event;

$event = MyRadio_Event::getInstance($_REQUEST['eventid']);

CoreUtils::getTemplateObject()->setTemplate('Events/viewEvent.twig')
    ->addVariable('event', $event)
    ->render();
