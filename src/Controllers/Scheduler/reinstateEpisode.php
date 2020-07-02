<?php
/**
 * Presents a form to the user to enable them to reinstate an Episode.
 */
use \MyRadio\Config;
use \MyRadio\MyRadioException;
use \MyRadio\MyRadio\URLUtils;
use \MyRadio\ServiceAPI\MyRadio_Timeslot;
use MyRadio\ServiceAPI\MyRadio_User;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //Submitted
    //Get data
    $data = MyRadio_Timeslot::getReinstateRequest()->readValues();
    //Cancel
    /** @var MyRadio_Timeslot $timeslot */
    $timeslot = MyRadio_Timeslot::getInstance($data['show_season_timeslot_id']);
    $result = $timeslot->reinstateTimeslot($data['reason']);

    if (!$result) {
        $message = 'This episode couldn\'t be reinstated.';
    } else if (MyRadio_User::getCurrentUser()->hasAuth(AUTH_DELETESHOWS)) {
        $message = 'Episode reinstated.';
    } else {
        $message = 'Your reinstatement request has been sent. You will receive an email informing you of updates.';
    }

    URLUtils::backWithMessage($message);
} else {
    //Not Submitted

    if (!isset($_REQUEST['show_season_timeslot_id'])) {
        throw new MyRadioException('No timeslotid provided.', 400);
    }

    MyRadio_Timeslot::getReinstateRequest()->render();
}
