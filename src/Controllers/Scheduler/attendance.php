<?php

/**
 * Shows statistics about members actually turning up for their shows.
 */
use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\ServiceAPI\MyRadio_Season;

$data = [];

foreach (MyRadio_Season::getAllSeasonsInLatestTerm() as $season) {
    $info = $season->getAttendanceInfo();
    $data[] = [
        'title' => $season->getMeta('title'),
        'percent' => (int) $info[0],
        'missed' => (int) $info[1],
    ];
}

CoreUtils::getTemplateObject()->setTemplate('table.twig')
    ->addVariable('title', 'Show Attendence')
    ->addVariable('tabledata', $data)
    ->addVariable('tablescript', 'myradio.Scheduler.attendance')
    ->render();
