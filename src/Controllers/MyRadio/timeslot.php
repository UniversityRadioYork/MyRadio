<?php

/**
 * Allows users to select the timeslot they are working with
 * 
 * @author Lloyd Wallis
 * @data 20140102
 * @package MyRadio_Core
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //Submitted
    $timeslot = MyRadio_Timeslot::getInstance($_POST['timeslotid']);
    //Can the user access this timeslot?
    if (!($timeslot->getSeason()->getShow()->isCurrentUserAnOwner() or CoreUtils::hasPermission(AUTH_EDITSHOWS))) {
        require_once 'Controllers/Errors/403.php';
    } else {
        $_SESSION['timeslotid'] = $timeslot->getID();
        $_SESSION['timeslotname'] = CoreUtils::happyTime($timeslot->getStartTime());
        //Handle sign-ins
        foreach ($_REQUEST['signin'] as $memberid) {
            $timeslot->signIn(User::getInstance($memberid));
        }
        header('Location: '.($_POST['next'] !== '' ? $_POST['next'] : Config::$base_url));
    }
} else {
    //Not Submitted
    $twig = CoreUtils::getTemplateObject()->setTemplate('MyRadio/timeslot.twig')
            ->addVariable('title', 'Timeslot Select')
            ->addVariable('allTimeslots', 'unavailable')
            ->addVariable('next', $_GET['next']);

    $data = [];
    /**
     * People with AUTH_EDITSHOWS can see all timeslots here
     */
    $shows = User::getInstance()->getShows();
    if (CoreUtils::hasPermission(AUTH_EDITSHOWS)) {
        if (isset($_GET['all'])) {
            $shows = MyRadio_Show::getAllShows();
            $twig->addVariable('allTimeslots', 'on');
        } else {
            $twig->addVariable('allTimeslots', 'off');
        }
    }

    foreach ($shows as $show) {
        foreach ($show->getAllSeasons() as $season) {
            $data[$show->getMeta('title')][] = array_map(function($x) {
                return [$x->getID(), $x->getStartTime()];
            }, $season->getAllTimeslots());
        }
    }
    $twig->addVariable('timeslots', $data)->render();
}