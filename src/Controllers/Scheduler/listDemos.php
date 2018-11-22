<?php
/**
 * @todo Proper Documentation
 */
use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\MyRadio\URLUtils;
use \MyRadio\ServiceAPI\MyRadio_Demo;
use \MyRadio\ServiceAPI\MyRadio_User;

$demos = MyRadio_Demo::listDemos();

$twig = CoreUtils::getTemplateObject();

$tabledata = [];

$currentUser = MyRadio_User::getInstance();

foreach ($demos as $demo) {
    if ($currentUser->hasAuth(AUTH_CANCELDEMOS)) {
        $demo['attending'] = MyRadio_Demo::usersAttendingDemo($demo['show_season_timeslot_id']);

        if (MyRadio_Demo::isDemoEmpty($demo['show_season_timeslot_id'])) {
            $demo['attending'] = 'Empty';
            $demo = addCancelButton($demo);
        }
    } elseif ($currentUser->hasAuth(AUTH_ADDDEMOS)) {
        $demo['attending'] = MyRadio_Demo::usersAttendingDemo($demo['show_season_timeslot_id']);

        if (MyRadio_Demo::isDemoEmpty($demo['show_season_timeslot_id'] && MyRadio_Demo::getDemoer() == $currentUser)) {
            $demo['attending'] = 'Empty';
            $demo = addCancelButton($demo);
        }
    } else {
        if (MyRadio_Demo::isUserAttendingDemo($demo['show_season_timeslot_id'], $currentUser->getID())) {
            $demo['attending'] = 'You are attending this demo';
            $demo['join'] = [
                'display' => 'text',
                'value' => 'Leave',
                'url' => URLUtils::makeURL('Scheduler', 'leaveDemo', ['demoid' => $demo['show_season_timeslot_id']]),
            ];
        } elseif (MyRadio_Demo::isSpaceOnDemo($demo['show_season_timeslot_id'])) {
            $demo['attending'] = 'Space available!';
            $demo['join'] = [
                'display' => 'text',
                'value' => 'Join',
                'url' => URLUtils::makeURL('Scheduler', 'attendDemo', ['demoid' => $demo['show_season_timeslot_id']]),
            ];
        } else {
            $demo['attending'] = 'Demo full';
            $demo['join'] = ['display' => 'none'];
        }
    }
    $tabledata[] = $demo;
}

if (empty($tabledata)) {
    $tabledata = [['', '', '', '', 'Error' => 'There are currently no training slots available.']];
}

function addCancelButton($demo)
{
    $demo['cancel'] = [
    'display' => 'icon',
    'value' => 'trash',
    'title' => 'Cancel Demo',
    'url' => URLUtils::makeURL(
        'Scheduler',
        'cancelEpisode',
        ['show_season_timeslot_id' => $demo['show_season_timeslot_id']]
    ),
    ];
    return $demo;
}

//print_r($tabledata);
$twig->setTemplate('table.twig')
    ->addVariable('title', 'Upcoming Training Slots')
    ->addVariable('tabledata', $tabledata)
    ->addVariable('tablescript', 'myradio.scheduler.demolist');

if (isset($_REQUEST['msg'])) {
    switch ($_REQUEST['msg']) {
        case 0: //joined
            $twig->addInfo('You have successfully been added to this session.');
            break;
        case 1: //full
            $twig->addError('Sorry, but a maximum two people can join a session.');
            break;
        case 2: //attending already
            $twig->addError('You can only attend one session at a time.');
            break;
        case 3: // Left session
            $twig->addInfo('You have left the training session.');
    }
}

$twig->render();
