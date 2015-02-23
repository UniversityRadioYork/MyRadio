<?php
/**
 *
 * @todo Proper Documentation
 * @package MyRadio_Scheduler
 */

use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\ServiceAPI\MyRadio_Season;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //Submitted
    $season = MyRadio_Season::getInstance($_SESSION['myury_working_with_season']);

    unset($_SESSION['myury_working_with_season']);

    $data = $season->getAllocateForm()->readValues();

    $season->schedule($data);

    CoreUtils::backWithMessage('Season Allocated!');

} else {
    //Not Submitted
    $season = MyRadio_Season::getInstance($_REQUEST['show_season_id']);

    /**
     * @todo WHY IS THIS IN THE SESSION
     */
    $_SESSION['myury_working_with_season'] = $season->getID();

    $season
        ->getAllocateForm()
        ->render($season->toDataSource());
}
