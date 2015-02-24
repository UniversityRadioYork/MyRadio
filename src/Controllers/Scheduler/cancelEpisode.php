<?php
/**
 * Presents a form to the user to enable them to cancel an Episode
 *
 * @package MyRadio_Scheduler
 */

use \MyRadio\MyRadioException;
use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\ServiceAPI\MyRadio_Timeslot;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //Submitted
    //Get data
    $data = MyRadio_Timeslot::getCancelForm()->readValues();
    //Cancel
    $timeslot = MyRadio_Timeslot::getInstance($data['show_season_timeslot_id']);
    $result = $timeslot->cancelTimeslot($data['reason']);

    if (!$result) {
        $message = 'Your cancellation request could not be processed at this time. '
            .'Please contact programming@ury.org.uk instead.';
    } else {
        $message = 'Your cancellation request has been sent. You will receive an email informing you of updates.';
    }

    CoreUtils::backWithMessage($message);

} else {
    //Not Submitted

    if (!isset($_REQUEST['show_season_timeslot_id'])) {
        throw new MyRadioException('No timeslotid provided.', 400);
    }

    MyRadio_Timeslot::getCancelForm()->render();
}
