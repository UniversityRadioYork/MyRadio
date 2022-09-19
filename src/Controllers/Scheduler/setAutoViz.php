<?php

use MyRadio\MyRadio\URLUtils;
use MyRadio\MyRadio\AuthUtils;
use MyRadio\ServiceAPI\MyRadio_AutoVizConfiguration;
use MyRadio\ServiceAPI\MyRadio_Timeslot;

/** @var MyRadio_Timeslot $timeslot */
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

$cfg = MyRadio_AutoVizConfiguration::getConfigForTimeslot($timeslot->getID());
if ($cfg === null) {
    $cfg = MyRadio_AutoVizConfiguration::create($timeslot->getID(), $_REQUEST['value'] === 'true', null, null);
} else {
    $cfg->update($_REQUEST['value'] === 'true', null, null);
}

URLUtils::backWithMessage('Updated successfully!');
