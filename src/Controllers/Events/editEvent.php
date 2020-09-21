<?php

use MyRadio\MyRadio\AuthUtils;
use MyRadio\MyRadio\URLUtils;
use MyRadio\ServiceAPI\MyRadio_Event;
use MyRadio\ServiceAPI\MyRadio_User;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = MyRadio_Event::getForm()->readValues();
    if (empty($data['id'])) {
        // create new
        $event = MyRadio_Event::create($data);
        URLUtils::redirectWithMessage(
            'Events',
            'viewEvent',
            'Your event has been created.',
            [
                'eventid' => $event->getID()
            ]
        );
    } else {
        // edit
        /** @var MyRadio_Event $event */
        $event = MyRadio_Event::getInstance($data['id']);

        // check permissions
        $event->checkEditPermissions();

        if ($data['title'] !== $event->getTitle()) {
            $event->setTitle($data['title']);
        }
        if ($data['description_html'] !== $event->getDescriptionHtml()) {
            $event->setDescriptionHtml($data['description_html']);
        }
        if ($data['start_time'] !== $event->getStartTime()) {
            $event->setStartTime($data['start_time']);
        }
        if ($data['end_time'] !== $event->getEndTime()) {
            $event->setEndTime($data['end_time']);
        }

        URLUtils::redirectWithMessage(
            'Events',
            'viewEvent',
            'Event updated.',
            [
                'eventid' => $event->getID()
            ]
        );
    }
} else {
    if (isset($_REQUEST['eventid'])) {
        // editing
        /** @var MyRadio_Event $event */
        $event = MyRadio_Event::getInstance($_REQUEST['eventid']);

        // check permissions
        $event->checkEditPermissions();

        $event->getEditForm()->render();
    } else {
        // creating new
        MyRadio_Event::getForm()
            ->render();
    }
}
