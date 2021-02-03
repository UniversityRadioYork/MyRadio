<?php

/**
 * Controller for making basic programming and scheduling tasks easier,
 * by giving user's a better interface
 * 
 * 1. List Future Timeslots
 */

use MyRadio\MyRadio\CoreUtils;
use MyRadio\ServiceAPI\MyRadio_User;

$upcomingTimeslots = MyRadio_User::getInstance($_SESSION["memberid"])->getUpcomingTimeslots();

CoreUtils::getTemplateObject()->setTemplate("table.twig")
    ->addVariable("tablescript", "myradio.scheduler.programming")
    ->addVariable("title", "Upcoming Timeslots")
    ->addVariable("tabledata", CoreUtils::setToDataSource($upcomingTimeslots))
    ->render();