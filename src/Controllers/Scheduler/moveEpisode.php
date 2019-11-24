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
    //Get data
    $data = MyRadio_Timeslot::getCancelForm()->readValues();
    //Cancel
    $timeslot = MyRadio_Timeslot::getInstance($data['show_season_timeslot_id']);
    $result = $timeslot->moveTimeslot(
        $data['new_start_time'],
        $data['new_end_time']
    );

    $message = 'Move successful.';

    URLUtils::backWithMessage($message);
} else {
    //Not Submitted

    if (!isset($_REQUEST['show_season_timeslot_id'])) {
        throw new MyRadioException('No timeslotid provided.', 400);
    }

    MyRadio_Timeslot::getMoveForm()->render();
}
