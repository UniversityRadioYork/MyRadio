<?php

use MyRadio\Config;
use MyRadio\MyRadio\URLUtils;
use MyRadio\ServiceAPI\MyRadio_Event;
use MyRadio\ServiceAPI\MyRadio_Timeslot;
use Spatie\IcalendarGenerator\Components\Calendar;

if (empty($_GET['token'])) {
    URLUtils::dataToJSON([
        'myradio_errors' => [
            'no_token'
        ]
    ]);
    exit;
}

$memberid = MyRadio_Event::validateCalendarToken($_GET['token']);
if ($memberid === null) {
    URLUtils::dataToJSON([
        'myradio_errors' => [
            'invalid'
        ]
    ]);
    exit;
}
// TODO: when we support RSVPs, filter here

$events = MyRadio_Event::getNext(25);

$cal = Calendar::create(Config::$long_name)
    ->refreshInterval(360);
foreach ($events as $evt) {
    $cal = $cal->event($evt->toIcalEvent());
}

$upcomingShows = MyRadio_Timeslot::getUserNextTimeslots($memberid, 25);
foreach ($upcomingShows as $ts) {
    $cal = $cal->event($ts->toIcalEvent());
}

header('Content-Type: text/calendar');
echo $cal->get();
