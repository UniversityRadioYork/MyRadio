<?php
/**
 * @todo Proper Documentation
 */

use MyRadio\MyRadio\AuthUtils;
use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\MyRadio\URLUtils;
use \MyRadio\ServiceAPI\MyRadio_Demo;
use \MyRadio\ServiceAPI\MyRadio_User;

$demos = MyRadio_Demo::listDemos();

$twig = CoreUtils::getTemplateObject();

$tabledata = [];

$currentUser = MyRadio_User::getInstance();

foreach ($demos as $demo) {
    $demo_object = MyRadio_Demo::getInstance($demo["demo_id"]);
    if (AuthUtils::hasPermission(AUTH_ADDDEMOS)) {
        $demo['attending'] = $demo_object->usersAttendingDemo();
        $demo['join'] = [
            'display' => 'text',
            'value' => 'Edit Demo',
            'url' => URLUtils::makeURL('Training', 'createDemo', ['demo_id' => $demo['demo_id']]),
        ];
        $demo['cancel'] = [
            'display' => 'icon',
            'value' => 'trash',
            'title' => 'Cancel Demo',
            'url' => URLUtils::makeURL('Training', 'cancelDemo', ['demo_id' => $demo['demo_id']]),
        ];
    } else {
        if ($demo_object->isUserAttendingDemo($currentUser->getID())) {
            if ($demo_object->tooCloseToStart()) {
                $demo['attending'] = 'You are attending this demo. Please contact your trainer if you can no longer make it.';
                $demo['join'] = [
                    'display' => 'none',
                ];
            } else {
                $demo['attending'] = 'You are attending this demo';
                $demo['join'] = [
                    'display' => 'text',
                    'value' => 'Leave',
                    'url' => URLUtils::makeURL('Training', 'leaveDemo', ['demoid' => $demo['demo_id']]),
                ];
            }
        } elseif ($demo_object->isSpaceOnDemo()) {
            if ($demo_object->tooCloseToStart()) {
                $demo['attending'] = 'Too late to sign up';
                $demo['join'] = [
                    'display' => 'none',
                ];
            } else {
                $demo['attending'] = 'Space available!';
                $demo['join'] = [
                    'display' => 'text',
                    'value' => 'Join',
                    'url' => URLUtils::makeURL('Training', 'attendDemo', ['demoid' => $demo['demo_id']]),
                ];
            }
        } else {
            $demo['attending'] = 'Demo full';
            $demo['join'] = ['display' => 'none'];
        }
        $demo["cancel"] = '';
    }

    if ($demo['demo_link']) {
        $demo['demo_link'] = [
            "display" => "icon",
            "value" => "headphones",
            "title" => "Online Training"
        ];
    } else {
        $demo['demo_link'] = [
            "display" => "icon",
            "value" => "user",
            "title" => "In Person Training"
        ];
    }
    $tabledata[] = $demo;
}

if (empty($tabledata)) {
    $tabledata = [['', '', '', '', '', 'Error' => 'There are currently no training slots available.', '']];
}

//print_r($tabledata);
$twig->setTemplate('table.twig')
    ->addVariable('title', 'Upcoming Training Slots')
    ->addVariable('tabledata', $tabledata)
    ->addVariable('tablescript', 'myradio.training.demolist');

if (isset($_REQUEST['msg'])) {
    switch ($_REQUEST['msg']) {
        case 0: //joined
            $twig->addInfo('You have successfully been added to this session.');
            break;
        case 1: //full
            $twig->addError('Sorry, but too many people are already attending this session.');
            break;
        case 2: //attending already
            $twig->addError('You can only attend one session for that training at a time.');
            break;
        case 3: //too late
            $twig->addError('It is now too late to join this session. If you still wish to attend, please contact the trainer directly to see if there is space available.');
            break;
        case -1: // Left session
            $twig->addInfo('You have left the training session.');
            break;
        case -3: //too late
            $twig->addError('It is now too late to leave this session. If you cannot make it, please contact the trainer directly.');
            break;
    }
}

$twig->render();
