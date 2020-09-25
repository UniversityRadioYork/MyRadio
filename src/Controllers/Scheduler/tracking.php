<?php

/**
 * Information about user locations
 */

use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\ServiceAPI\MyRadio_Season;
use MyRadio\ServiceAPI\MyRadio_Timeslot;

$data = [];
$no_track = MyRadio_Timeslot::getLocationName(5); //WebStudio

foreach (MyRadio_Season::getAllSeasonsInLatestTerm() as $season) {
    foreach ($season->getAllTimeslots() as $timeslot) {
        if ($timeslot->getStartTime() < time()) {
            foreach ($timeslot->getSigninInfo() as $info) {
                if ($info['location'] != $no_track) {
                    if (isset($info['user'])) {
                        $data[] =
                            [
                                "type" => "URY Member",
                                "info" => $info['user']->getName() . ($info['user']->getEduroam() ? " (" . $info['user']->getEduroam() . ")" : ""),
                                "location" => $info['location'],
                                "time" => CoreUtils::happyTime($info['time'])
                            ];
                    } else if ($info['guest_info']) {
                        $data[] =
                            [
                                "type" => "Guest",
                                "info" => $info['guest_info'],
                                "location" => $info['location'],
                                "time" => CoreUtils::happyTime($info['time'])
                            ];
                    }
                }
            }
        }
    }
}

CoreUtils::getTemplateObject()->setTemplate('table.twig')
    ->addVariable('title', 'Tracking Information')
    ->addVariable('tabledata', $data)
    ->addVariable('tablescript', 'myradio.scheduler.tracking')
    ->render();
