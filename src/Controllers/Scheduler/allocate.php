<?php
/**
 * @todo Proper Documentation
 */
use \MyRadio\MyRadio\URLUtils;
use \MyRadio\ServiceAPI\MyRadio_Season;
use \MyRadio\ServiceAPI\MyRadio_Scheduler;
use \MyRadio\ServiceAPI\MyRadio_Term;
use \MyRadio\MyRadioException;

$current_term_info = MyRadio_Term::getActiveApplicationTerm();
$term_weeks = $current_term_info->getTermWeeks();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //Submitted

    // Note Slightly ugly hack to get the season ID from the submitted form
    $season = MyRadio_Season::getInstance($_POST['sched_allocate-season_id']);
    $data = $season->getAllocateForm()->readValues();
    $season->schedule($data, $term_weeks);

    URLUtils::redirectWithMessage('Scheduler', 'default', 'Season Allocated!');
} else {
    //Not Submitted
    if (empty(MyRadio_Term::getActiveApplicationTerm())) {
        throw new MyRadioException('There is not currently a term you can apply/schedule for.', 400);
    } else {
        $season = MyRadio_Season::getInstance($_REQUEST['show_season_id']);
        $season->getAllocateForm()
            ->render($season->toDataSource());
    }
}
