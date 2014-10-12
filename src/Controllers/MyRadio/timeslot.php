<?php

/**
 * Allows users to select the timeslot they are working with
 *
 * @author Lloyd Wallis
 * @data 20140102
 * @package MyRadio_Core
 */

use \MyRadio\Config;
use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\ServiceAPI\MyRadio_User;
use \MyRadio\ServiceAPI\MyRadio_Timeslot;
use \MyRadio\ServiceAPI\MyRadio_Show;

function setupTimeslot($timeslot)
{
    //Can the user access this timeslot?
    if (!((!empty($timeslot) and $timeslot->getSeason()->getShow()->isCurrentUserAnOwner())
          or CoreUtils::hasPermission(AUTH_EDITSHOWS))) {
        require_once 'Controllers/Errors/403.php';
    } else {
        $_SESSION['timeslotid'] = $timeslot->getID();
        $_SESSION['timeslotname'] = CoreUtils::happyTime($timeslot->getStartTime());
        //Handle sign-ins
        foreach ($_REQUEST['signin'] as $memberid) {
            $timeslot->signIn(MyRadio_User::getInstance($memberid));
        }
        header('Location: ' . ($_REQUEST['next'] !== '' ? $_REQUEST['next'] : Config::$base_url));
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //Submitted
    setupTimeslot(MyRadio_Timeslot::getInstance($_POST['timeslotid']));
} elseif (isset($_GET['current']) && $_GET['current'] && CoreUtils::hasPermission(AUTH_EDITSHOWS)) {
    setupTimeslot(MyRadio_Timeslot::getCurrentTimeslot());
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
    $shows = MyRadio_User::getInstance()->getShows();
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
            $data[$show->getMeta('title')][] = array_map(function ($x) {
                return [$x->getID(), $x->getStartTime(), $x->getEndTime()];
            }, $season->getAllTimeslots());
        }
    }
    $twig->addVariable('timeslots', $data)->render();
}
