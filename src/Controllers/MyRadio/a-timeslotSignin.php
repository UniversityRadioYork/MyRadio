<?php
/**
 * Returns the presenter signin data for the given Timeslot
 * (if the user has access to this data)
 *
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20140102
 * @package MyRadio_Core
 */

$ts = MyRadio_Timeslot::getInstance($_REQUEST['timeslotid']);
if ($ts->getSeason()->getShow()->isCurrentUserAnOwner()
        or CoreUtils::hasPermission(AUTH_EDITSHOWS)) {
    $data = $ts->getSigninInfo();
    CoreUtils::dataToJSON($data);
} else {
    require_once 'Controllers/Errors/403.php';
}
