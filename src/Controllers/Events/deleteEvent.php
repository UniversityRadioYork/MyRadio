<?php

use MyRadio\MyRadio\URLUtils;
use MyRadio\MyRadioException;
use MyRadio\ServiceAPI\MyRadio_Event;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    throw new MyRadioException('Method not allowed', 405);
}

/** @var MyRadio_Event $event */
$event = MyRadio_Event::getInstance($_REQUEST['eventid']);
$event->checkEditPermissions();
$event->delete();

URLUtils::redirectWithMessage(
    'Events',
    'calendar',
    'Event deleted.'
);
