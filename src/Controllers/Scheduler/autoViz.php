<?php

use MyRadio\MyRadio\CoreUtils;
use MyRadio\MyRadio\URLUtils;
use MyRadio\ServiceAPI\MyRadio_AutoVizClip;
use MyRadio\ServiceAPI\MyRadio_Timeslot;

$timeslots = array_merge(MyRadio_Timeslot::getCurrentUserPreviousTimeslots(), MyRadio_Timeslot::getCurrentUserNextTimeslots());

$rows = [];
foreach ($timeslots as $timeslot) {
    if (is_array($timeslot) || $timeslot === null) {
        continue;
    }
    $clips = MyRadio_AutoVizClip::getClipsForTimeslot($timeslot->getID());
    $rows[] = [
        'title' => $timeslot->getMeta('title'),
        'start_time' => CoreUtils::happyTime($timeslot->getStartTime()),
        'togglelink' => ($timeslot->getStartTime() < time()) ? [
            'display' => 'icon',
            'value' => 'times',
            'title' => 'Show is in the past!',
            'url' => '#',
        ] : (($timeslot->getAutoViz()) ? [
            'display' => 'text',
            'value' => 'Enabled',
            'title' => 'Disable Automatic Visualisation',
            'url' => URLUtils::makeURL('Scheduler', 'setAutoViz', ['timeslotid' => $timeslot->getID(), 'value' => 'false']),
        ] : [
            'display' => 'text',
            'value' => 'Disabled',
            'title' => 'Enable Automatic Visualisation',
            'url' => URLUtils::makeURL('Scheduler', 'setAutoViz', ['timeslotid' => $timeslot->getID(), 'value' => 'true']),
        ]),
        'clipslink' => empty($clips) ? 'No clips available' : [
            'display' => 'text',
            'value' => 'Clips',
            'title' => 'Access all the clips from this show',
            'url' => URLUtils::makeURL('Scheduler', 'autoVizClips', ['timeslotid' => $timeslot->getID()])
        ]
    ];
}

CoreUtils::getTemplateObject()->setTemplate('table.twig')
    ->addVariable('tablescript', 'myradio.scheduler.autoViz')
    ->addVariable('title', 'Automatically Visualised Shows')
    ->addVariable('tabledata', $rows)
    ->render();
