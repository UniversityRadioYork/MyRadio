<?php

/**
 * Controller for making basic programming and scheduling tasks easier,
 * by giving user"s a better interface
 * 
 * 1. List Future Timeslots
 * 
 * @todo Select timeslot link
 * @todo Request new episodes (Big Deal)
 * @todo Apply for more of your old shows (also needs terms page..aahh)
 */

use MyRadio\MyRadio\CoreUtils;
use MyRadio\MyRadio\URLUtils;
use MyRadio\ServiceAPI\MyRadio_User;

$fullUpcomingTimeslots = MyRadio_User::getInstance($_SESSION["memberid"])->getUpcomingTimeslots();

$timeslots = [];

foreach ($fullUpcomingTimeslots as $timeslot) {
    $timeslots[] = [
        "Title" => $timeslot->getMeta("title"),
        "Time" => CoreUtils::happyTime($timeslot->getStartTime()),
        "Duration" => $timeslot->getDuration(),
        "Cancel" => [
                "display" => "text",
                "value" => "Cancel Episode",
                "title" => "Cancel Episode",
                "url" => URLUtils::makeURL(
                    "Scheduler",
                    "cancelEpisode",
                    ["show_season_timeslot_id" => $timeslot->getID()]
                ),
            ]
    ];
}

CoreUtils::getTemplateObject()->setTemplate("table.twig")
    ->addVariable("tablescript", "myradio.scheduler.programming")
    ->addVariable("title", "Your Upcoming Timeslots")
    ->addVariable("tabledata", $timeslots)
    ->render();