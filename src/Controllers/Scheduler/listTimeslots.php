<?php
/**
 * Controller for outputting a Datatable of Seasons within the specified Show
 * @package MyRadio_Scheduler
 * @todo This requires manual permission checks as it needs interesting things
 */

use MyRadio\MyRadio\CoreUtils;
use MyRadio\ServiceAPI\ServiceAPI;
use MyRadio\ServiceAPI\MyRadio_Season;

$season = MyRadio_Season::getInstance($_GET['show_season_id']);

CoreUtils::getTemplateObject()->setTemplate('table.twig')
    ->addVariable('tablescript', 'myury.scheduler.timeslotlist')
    ->addVariable('title', 'Episodes of '.$season->getMeta('title'))
    ->addVariable('tabledata', ServiceAPI::setToDataSource($season->getAllTimeslots()))
    ->render();
