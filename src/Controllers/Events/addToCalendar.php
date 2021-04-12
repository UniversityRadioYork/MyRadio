<?php

use MyRadio\MyRadio\CoreUtils;
use MyRadio\MyRadio\URLUtils;
use MyRadio\MyRadioException;
use MyRadio\ServiceAPI\MyRadio_Event;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($_POST['action']) {
        case 'regenerate':
            MyRadio_Event::revokeCalendarTokenFor();
            URLUtils::backWithMessage('Calendar link reset.');
            exit;
        default:
            throw new MyRadioException('Unknown action', 400);
    }
}

$token = MyRadio_Event::getCalendarTokenFor();
if ($token === null) {
    $token = MyRadio_Event::createCalendarTokenFor();
}

CoreUtils::getTemplateObject()
    ->setTemplate('Events/addToCalendar.twig')
    ->addVariable('link', URLUtils::makeURL('Events', 'iCal', [
        'token' => $token
    ]))
    ->render();
