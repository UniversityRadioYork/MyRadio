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
                if (isset($info["location"]) && $info["location"] != $no_track) {
                    if (isset($info["user"])) {
                        $eduroam = $info["user"]->getEduroam();
                        $data[] = [
                            "type" => "URY Member",
                            "info" => $info["user"]->getName() . ($eduroam ? " ($eduroam)" : ""),
                            "location" => $info["location"],
                            "time" => CoreUtils::happyTime($info["time"]),
                            "unix" => $info["time"]
                        ];
                    } elseif ($info["guest_info"]) {
                        $data[] = [
                            "type" => "Guest",
                            "info" => [
                                "display" => "html",
                                "html" => nl2br($info["guest_info"])
                            ],
                            "location" => $info["location"],
                            "time" => CoreUtils::happyTime($info["time"]),
                            "unix" => $info["time"]
                        ];
                    }
                }
            }
        }
    }
}

CoreUtils::getTemplateObject()->setTemplate("table.twig")
    ->addVariable("title", "Tracking Information")
    ->addVariable("tabledata", $data)
    ->addVariable("tablescript", "myradio.scheduler.tracking")
    ->render();
