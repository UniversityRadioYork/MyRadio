<?php
/**
 * Returns the presenter signin data for the given Timeslot
 * (if the user has access to this data).
 */
use \MyRadio\MyRadio\AuthUtils;
use \MyRadio\MyRadio\URLUtils;
use \MyRadio\ServiceAPI\MyRadio_Timeslot;

$ts = MyRadio_Timeslot::getInstance($_REQUEST['timeslotid']);
if ($ts->getSeason()->getShow()->isCurrentUserAnOwner()
    || AuthUtils::hasPermission(AUTH_EDITSHOWS)
) {
    $data = $ts->getSigninInfo();
    URLUtils::dataToJSON($data);
} else {
    require_once 'Controllers/Errors/403.php';
}
