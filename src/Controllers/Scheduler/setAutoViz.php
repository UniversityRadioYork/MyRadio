<?php

use MyRadio\MyRadio\URLUtils;
use MyRadio\MyRadio\AuthUtils;
use MyRadio\ServiceAPI\MyRadio_Timeslot;

$timeslot = MyRadio_Timeslot::getInstance($_REQUEST['timeslotid']);

//Check the user has permission to edit this show
if (!$timeslot->getSeason()->isCurrentUserAnOwner()) {
    AuthUtils::requirePermission(AUTH_EDITSHOWS);
}

// And that it's not in the past
if ($timeslot->getStartTime() < time()) {
    URLUtils::backWithMessage('That show is in the past!');
    die;
}

$timeslot->setAutoViz($_REQUEST['value'] === 'true');

URLUtils::backWithMessage('Updated successfully!');
