<?php
/**
 * Presents a form to the user to enable them to move an Episode.
 */
use \MyRadio\Config;
use MyRadio\MyRadio\AuthUtils;
use \MyRadio\MyRadioException;
use \MyRadio\MyRadio\URLUtils;
use \MyRadio\ServiceAPI\MyRadio_Timeslot;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //Submitted
    // @todo this is a bit of a hack
    $timeslot = MyRadio_Timeslot::getInstance($_REQUEST['sched_move-show_season_timeslot_id']);
    //Get data
    $data = $timeslot->getMoveForm()->readValues();
    //Move
    $result = $timeslot->moveTimeslot(
        $data['new_start_time'],
        $data['new_end_time']
    );

    if ($result) {
        $message = 'Move successful.';
    } else {
        $message = 'Something didn\'t work! Please ping Computing.';
    }

    URLUtils::backWithMessage($message);
} else {
    //Not Submitted

    if (!isset($_REQUEST['show_season_timeslot_id'])) {
        throw new MyRadioException('No timeslotid provided.', 400);
    }

    $timeslot = MyRadio_Timeslot::getInstance($_REQUEST['show_season_timeslot_id']);

    $timeslot->getMoveForm()->render();
}
