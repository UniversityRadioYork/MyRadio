<?php
/**
 *
 * @author Andy Durant <aj@ury.org.uk>
 * @version 20140624
 * @package MyRadio_Scheduler
 */

use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\ServiceAPI\MyRadio_Season;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //Submitted
    $data = MyRadio_Season::getRejectForm()->readValues();

    MyRadio_Season::getInstance($data['season_id'])
        ->reject($data['reason'], $data['notify_user']);

    CoreUtils::backWithMessage("Season Rejected!");

} else {
    //Not Submitted

    $season = MyRadio_Season::getInstance($_REQUEST['show_season_id']);

    MyRadio_Season::getRejectForm()
        ->setFieldValue('season_id', $season->getID())
        ->render();
}
