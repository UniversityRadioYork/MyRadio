<?php
/**
 * Presents a form to the user to enable them to cancel an Episode
 *
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 05012013
 * @package MyRadio_Scheduler
 */

//The Form definition
require 'Models/Scheduler/reasonfrm.php';
//Get data
$data = $form->readValues();
//Cancel
$timeslot = MyRadio_Timeslot::getInstance($data['show_season_timeslot_id']);
$result = $timeslot->cancelTimeslot($data['reason']);

if (!$result) {
    $message = 'Your cancellation request could not be processed at this time. '
        .'Please contact programming@ury.org.uk instead.';
} else {
    $message = 'Your cancellation request has been sent. You will receive an email informing you of updates.';
}

CoreUtils::redirect(
    'Scheduler',
    'listTimeslots',
    array(
        'show_season_id' => $timeslot->getSeason()->getID(),
        'message' => base64_encode($message)
    )
);
