<?php

namespace MyRadio\Daemons;

use MyRadio\Config;
use MyRadio\ServiceAPI\MyRadio_Timeslot;
use MyRadio\ServiceAPI\MyRadio_Season;
use MyRadio\MyRadio\CoreUtils;
use MyRadio\MyRadioEmail;
use MyRadio\ServiceAPI\MyRadio_List;

class MyRadio_TrackAndTraceDaemon extends \MyRadio\MyRadio\MyRadio_Daemon
{
    public static function isEnabled()
    {
        return Config::$d_TrackAndTrace_enabled;
    }

    public static function run()
    {
        $weekkey = __CLASS__ . '_last_run_weekly';
        if (self::getVal($weekkey) > time() - 604700) {
            return;
        }

        self::generateTrackAndTraceReport();

        // Done
        self::setVal($weekkey, time());
    }

    private static function generateTrackAndTraceReport()
    {
        $table = "<table>";
        $table .= "<tr><th></th><th>Information</th><th>Location</th><th>Time</th></tr>";

        $data = [];
        $no_track = MyRadio_Timeslot::getLocationName(5); //WebStudio

        foreach (MyRadio_Season::getAllSeasonsInLatestTerm() as $season) {
            foreach ($season->getAllTimeslots() as $timeslot) {
                if (
                    $timeslot->getStartTime() < time()
                    && $timeslot->getStartTime() > time() - 604800
                ) {
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

        foreach ($data as $row) {
            $table .= "<tr><td>" . $row["type"]
                . "</td><td>" . $row["info"]
                . "</td><td>" . $row["location"]
                . "</td><td>" . $row["time"]
                . "</td></tr>\r\n";
        }

        $table .= "</table>";

        MyRadioEmail::sendEmailToList(
            MyRadio_List::getByName("Management Team"),
            "Track and Trace Report",
            $table
        );
    }
}
