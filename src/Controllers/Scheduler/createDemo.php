<?php
/**
 * Allows URY Trainers to create demo slots for new members to attend.
 */
use \MyRadio\MyRadio\URLUtils;
use \MyRadio\ServiceAPI\MyRadio_Demo;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //Submitted
    $demoinfo = MyRadio_Demo::getForm()->readValues();
    if ($demoinfo['id']){
        // Update a Demo
        MyRadio_Demo::getInstance($demoinfo['id'])->editDemo($demoinfo['demo-datetime'], $demoinfo['demo-link'], $demoinfo['demo-training-type']);
    }else{
        // Create a New Demo
    MyRadio_Demo::registerDemo($demoinfo['demo-datetime'], $demoinfo['demo-link'], $demoinfo['demo-training-type']);
    }
    URLUtils::backWithMessage('Session Updated!');
} else {
    //Not Submitted
    if ($_REQUEST["demo_id"]){
        // Update Demo
        MyRadio_Demo::getInstance($_REQUEST["demo_id"])
        ->getEditForm()
        ->setTemplate("Scheduler/createDemo.twig")
        ->render();
    } else {
        // Create New Demo
    MyRadio_Demo::getForm()
        ->setTemplate('Scheduler/createDemo.twig')
        ->render();
    }
}
