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
    $demo_object = MyRadio_Demo::getInstance($demo["demo_id"]);
    if ($currentUser->hasAuth(AUTH_ADDDEMOS)) {
        $demo['attending'] = $demo_object->usersAttendingDemo();
        $demo['join'] = [
            'display' => 'text',
            'value' => 'Edit Demo',
            'url' => URLUtils::makeURL('Training', 'createDemo', ['demo_id' => $demo['demo_id']]),
        ];
        $demo["finish"] = [
            "display" => "icon",
            "value" => "ok",
            "title" => "Mark Attendees as Trained",
            "url" => URLUtils::makeURL("Training", "finishDemo", ["demo_id" => $demo["demo_id"]])
        ];
    } else {
        if ($demo_object->isUserAttendingDemo($currentUser->getID())) {
            $demo['attending'] = 'You are attending this demo';
            $demo['join'] = [
                'display' => 'text',
                'value' => 'Leave',
                'url' => URLUtils::makeURL('Training', 'leaveDemo', ['demoid' => $demo['demo_id']]),
            ];
        } elseif ($demo_object->isSpaceOnDemo()) {
            $demo['attending'] = 'Space available!';
            $demo['join'] = [
                'display' => 'text',
                'value' => 'Join',
                'url' => URLUtils::makeURL('Training', 'attendDemo', ['demoid' => $demo['demo_id']]),
            ];
        } else {
            $demo['attending'] = 'Demo full';
            $demo['join'] = ['display' => 'none'];
        }
        $demo["finish"] = "";
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
    $tabledata = [['', '', '', '', '', '', 'Error' => 'There are currently no training slots available.', '']];
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
            $twig->addError('Sorry, but a maximum two people can join a session.');
            break;
        case 2: //attending already
            $twig->addError('You can only attend one session for that training at a time.');
            break;
        case 3: // Left session
            $twig->addInfo('You have left the training session.');
            break;
        case 4: // Mark attendees as trained
            $twig->addInfo("You have marked the attendees as trained.");
            break;
    }
}

$twig->render();
