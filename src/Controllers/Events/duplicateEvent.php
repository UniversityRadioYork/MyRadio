<?php

use MyRadio\MyRadio\AuthUtils;
use MyRadio\MyRadio\URLUtils;
use MyRadio\ServiceAPI\MyRadio_Event;
use MyRadio\ServiceAPI\MyRadio_User;

// editing
/** @var MyRadio_Event $event */
$event = MyRadio_Event::getInstance($_REQUEST['eventid']);

// check permissions
$event->checkEditPermissions();

$vals = $event->toDataSource();
unset($vals['host']);
unset($vals['start']);
unset($vals['end']);
$vals['start_time'] = date('d/m/Y H:i', $event->getStartTime());
$vals['end_time'] = date('d/m/Y H:i', $event->getEndTime());

MyRadio_Event::getForm()
    ->setValues($vals)
    ->setSubtitle('Duplicating event ' . $event->getTitle() . '.')
    ->render();
