<?php

/**
 * Allows URY Trainers to create demo slots for new members to attend.
 */

use \MyRadio\MyRadio\URLUtils;
use \MyRadio\ServiceAPI\MyRadio_Demo;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //Submitted
    $demoinfo = MyRadio_Demo::getForm()->readValues();
    if ($demoinfo['id']) {
        // Update a Demo
        MyRadio_Demo::getInstance($demoinfo['id'])->editDemo(
            $demoinfo['demo_datetime'],
            $demoinfo['demo_training_type'],
            $demoinfo['demo_link']
        );
    } else {
        // Create a New Demo
        MyRadio_Demo::registerDemo($demoinfo['demo_datetime'], $demoinfo['demo_training_type'], $demoinfo['demo_link']);
    }
    URLUtils::backWithMessage('Session Updated!');
} else {
    //Not Submitted
    if ($_REQUEST["demo_id"]) {
        // Update Demo
        MyRadio_Demo::getInstance($_REQUEST["demo_id"])
            ->getEditForm()
            ->setTemplate("Training/createDemo.twig")
            ->render();
    } else {
        // Create New Demo
        MyRadio_Demo::getForm()
            ->setTemplate('Training/createDemo.twig')
            ->render();
    }
}
