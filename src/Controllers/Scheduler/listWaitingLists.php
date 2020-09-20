<?php
/**
 * @todo Proper Documentation
 */
use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\MyRadio\URLUtils;
use \MyRadio\ServiceAPI\MyRadio_Demo;
use MyRadio\ServiceAPI\MyRadio_TrainingStatus;
use \MyRadio\ServiceAPI\MyRadio_User;
use MyRadio\ServiceAPI\MyRadio_UserTrainingStatus;

$lists = MyRadio_Demo::userWaitingList();

$twig = CoreUtils::getTemplateObject();

$tabledata = [];

$currentUser = MyRadio_User::getInstance();

foreach ($lists as $list) {
    $list['presenterstatusid'] = MyRadio_UserTrainingStatus::getInstance($list['presenterstatusid']->getTitle());
    $list['link'] = [
        "display" => "text",
        "value" => "Leave Waiting List",
        "url" => URLUtils::makeURL("Scheduler", "leaveList", ["presenterstatusid" => $list["presenterstatusid"]])
    ];
    
    $tabledata[] = $list;
}

$can_be_awarded = MyRadio_TrainingStatus::getAllAwardableTo($_SESSION['memberid']);
foreach ($can_be_awarded as $status){
    $list = [
        "presenterstatusid" => $status->getID(),
        "date_added" => "",
        "link" => [
            "display" => "text",
            "value" => "Join Waiting List",
            "url" => URLUtils::makeURL("Scheduler", "joinList", ["presenterstatusid" => $status->getID()])
        ]
    ];

    $tabledata[] = $list;

}

$twig->setTemplate('table.twig')
    ->addVariable('title', 'My Training Waiting Lists')
    ->addVariable('tabledata', $tabledata)
    ->addVariable('tablescript', 'myradio.scheduler.waitinglist');

if (isset($_REQUEST['msg'])) {
    switch ($_REQUEST['msg']) {
        case 0: //joined
            $twig->addInfo("You have successfully been added to this waiting list. We'll let you know if a training session becomes available.");
            break;
        case 1: //left
            $twig->addInfo('You have left the waiting list.');
            break;
    }
}

$twig->render();
