<?php

use MyRadio\MyRadio\URLUtils;
use MyRadio\MyRadio\AuthUtils;
use MyRadio\MyRadio\CoreUtils;
use MyRadio\ServiceAPI\MyRadio_AutoVizClip;
use MyRadio\ServiceAPI\MyRadio_Timeslot;

$timeslot = MyRadio_Timeslot::getInstance($_REQUEST['timeslotid']);

//Check the user has permission to edit this show
if (!$timeslot->getSeason()->isCurrentUserAnOwner()) {
    AuthUtils::requirePermission(AUTH_EDITSHOWS);
}


$clips = MyRadio_AutoVizClip::getClipsForTimeslot($timeslot->getID());

$rows = [];
foreach ($clips as $clip) {
    $rows[] = [
        'type' => $clip->getType() === 'full_show' ? 'Full Show' : 'Clip',
        'start_time' => CoreUtils::happyTime($clip->getStartTime()),
        'end_time' => CoreUtils::happyTime($clip->getEndTime()),
        'downloadlink' => [
            'display' => 'text',
            'value' => 'Download',
            'title' => 'Download this clip',
            'url' => $clip->getPublicURL(),
        ]
    ];
}

CoreUtils::getTemplateObject()->setTemplate('table.twig')
    ->addVariable('tablescript', 'myradio.scheduler.autoVizClips')
    ->addVariable('title', 'Clips for ' . $timeslot->getMeta('title') . ' ' . CoreUtils::happyTime($timeslot->getStartTime()))
    ->addVariable('tabledata', $rows)
    ->render();
