<?php

/**
 * Allows users to select the timeslot they are working with.
 *
 * @data    20140102
 */

use \MyRadio\Config;
use \MyRadio\MyRadio\AuthUtils;
use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\MyRadio\URLUtils;
use MyRadio\ServiceAPI\MyRadio_Scheduler;
use \MyRadio\ServiceAPI\MyRadio_User;
use \MyRadio\ServiceAPI\MyRadio_Timeslot;
use \MyRadio\ServiceAPI\MyRadio_Show;

function setupTimeslot(MyRadio_Timeslot $timeslot)
{
    // No timeslot (probably jukebox)
    if (empty($timeslot)) {
        URLUtils::backWithMessage('Cannot select empty timeslot.');
    }

    //Can the user access this timeslot?
    if (!($timeslot->isCurrentUserAnOwner() || AuthUtils::hasPermission(AUTH_EDITSHOWS))) {
        $message = "You don't have permission to view this show";
        require_once 'Controllers/Errors/403.php';
    } else {
        MyRadio_Timeslot::setUserSelectedTimeslot($timeslot);
        //Handle sign-ins
        foreach (($_REQUEST['signin'] ?? []) as $memberid) {
            if (!isset($_REQUEST["location"]) || $_REQUEST["location"] == "unselected") {
                URLUtils::backWithMessage("You must select where you are doing your show");
                return;
            }
            $timeslot->signIn(MyRadio_User::getInstance($memberid), $_REQUEST['location']);
        }
        if (!empty($_REQUEST['guest_info'])) {
            $timeslot->signInGuests($_REQUEST['guest_info'], $_REQUEST['location']);
        }
        header('Location: ' . ($_REQUEST['next'] !== '' ? $_REQUEST['next'] : Config::$base_url));
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //Submitted
    if (isset($_POST['timeslotid'])) {
        setupTimeslot(MyRadio_Timeslot::getInstance($_POST['timeslotid']));
    } else {
        URLUtils::backWithMessage('Cannot select empty timeslot');
    }
} elseif (isset($_GET['current']) && $_GET['current'] && AuthUtils::hasPermission(AUTH_EDITSHOWS)) {
    //Submitted Current
    setupTimeslot(MyRadio_Timeslot::getCurrentTimeslot());
} elseif (!empty(Config::$contract_uri) && !MyRadio_User::getInstance()->hasSignedContract()) {
    $message = "You need to have signed the Presenter's Contract to view this. You'll find it in the main menu.";
    require_once 'Controllers/Errors/403.php';
} else {
    //Not Submitted
    $twig = CoreUtils::getTemplateObject()->setTemplate('MyRadio/timeslot.twig')
        ->addVariable('title', 'Timeslot Select')
        ->addVariable('allTimeslots', 'unavailable')
        ->addVariable('next', $_GET['next']);

    $data = [];
    /*
     * People with AUTH_EDITSHOWS can see all timeslots here
     */
    $shows = MyRadio_User::getInstance()->getShows();
    if (AuthUtils::hasPermission(AUTH_EDITSHOWS)) {
        if (isset($_GET['all'])) {
            $shows = MyRadio_Show::getAllShows();
            $twig->addVariable('allTimeslots', 'on');
        } else {
            $twig->addVariable('allTimeslots', 'off');
        }

        if (!is_null(MyRadio_Timeslot::getCurrentTimeslot())) {
            $twig->addVariable('currentAvaliable', 'true');
        }
    }

    foreach ($shows as $show) {
        foreach ($show->getAllSeasons() as $season) {
            $data[$show->getMeta('title')][] = array_map(
                function ($x) {
                    return [$x->getID(), $x->getStartTime(), $x->getEndTime()];
                },
                $season->getAllTimeslots()
            );
        }
    }
    $twig
        ->addVariable('timeslots', $data)
        ->addVariable('locations', MyRadio_Scheduler::getLocations())
        ->render();
}
