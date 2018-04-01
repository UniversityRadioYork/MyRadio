<?php
/**
 * @todo Proper Documentation
 */
use \MyRadio\MyRadio\URLUtils;
use \MyRadio\ServiceAPI\MyRadio_Season;
use \MyRadio\ServiceAPI\MyRadio_Scheduler;
use \MyRadio\MyRadioException;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //Submitted

    // Note Slightly ugly hack to get the season ID from the submitted form
    $season = MyRadio_Season::getInstance($_POST['sched_allocate-season_id']);
    $data = $season->getAllocateForm()->readValues();
    $season->schedule($data);

    URLUtils::redirectWithMessage('Scheduler', 'default', 'Season Allocated!');
} else {
    //Not Submitted
    if (empty(MyRadio_Scheduler::getActiveApplicationTermInfo())) {
        throw new MyRadioException('There is not currently a term you can apply/schedule for.', 400);
    } else {
        $season = MyRadio_Season::getInstance($_REQUEST['show_season_id']);
        $season->getAllocateForm()
            ->render($season->toDataSource());
    }
}
