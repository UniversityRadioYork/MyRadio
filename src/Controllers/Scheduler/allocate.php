<?php
/**
 * @todo Proper Documentation
 */
use \MyRadio\MyRadio\URLUtils;
use \MyRadio\ServiceAPI\MyRadio_Season;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //Submitted
    $season = MyRadio_Season::getInstance($_SESSION['myury_working_with_season']);

    unset($_SESSION['myury_working_with_season']);

    $data = $season->getAllocateForm()->readValues();

    $season->schedule($data);

    URLUtils::redirectWithMessage('Scheduler', 'default', 'Season Allocated!');
} else {
    //Not Submitted
    $season = MyRadio_Season::getInstance($_REQUEST['show_season_id']);

    /*
     * @todo WHY IS THIS IN THE SESSION
     */
    $_SESSION['myury_working_with_season'] = $season->getID();

    $season
        ->getAllocateForm()
        ->render($season->toDataSource());
}
