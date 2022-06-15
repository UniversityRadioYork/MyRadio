<?php

use MyRadio\MyRadio\URLUtils;
use MyRadio\MyRadio\AuthUtils;
use MyRadio\ServiceAPI\MyRadio_Timeslot;

$timeslot = MyRadio_Timeslot::getInstance($_REQUEST['timeslotid']);

//Check the user has permission to edit this show
if (!$timeslot->getShow()->isCurrentUserAnOwner()) {
    AuthUtils::requirePermission(AUTH_EDITSHOWS);
}

$timeslot->setAutoViz($_REQUEST['value'] === 'true');

URLUtils::backWithMessage('Updated successfully!');
