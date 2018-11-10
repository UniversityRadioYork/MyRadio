<?php
/**
 * Presents a form to the user to enable them to cancel an Episode.
 */
use \MyRadio\MyRadioException;
use \MyRadio\MyRadio\URLUtils;
use \MyRadio\ServiceAPI\MyRadio_Timeslot;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //Submitted
    //Get data
    $data = MyRadio_Timeslot::getCancelForm()->readValues();
    //Cancel
    $timeslot = MyRadio_Timeslot::getInstance($data['show_season_timeslot_id']);
    $result = $timeslot->cancelTimeslot($data['reason']);

    if (!$result) {
        $message = 'This episode is too close to its scheduled time to be automatically cancelled, '
            .'please contact programming@'.Config::$email_domain.' instead.';
    } else {
        $message = 'Your cancellation request has been sent. You will receive an email informing you of updates.';
    }

    URLUtils::backWithMessage($message);
} else {
    //Not Submitted

    if (!isset($_REQUEST['show_season_timeslot_id'])) {
        throw new MyRadioException('No timeslotid provided.', 400);
    }

    MyRadio_Timeslot::getCancelForm()->render();
}
