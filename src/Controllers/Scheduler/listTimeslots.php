<?php
/**
 * Controller for outputting a Datatable of Seasons within the specified Show
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 26122012
 * @package MyRadio_Scheduler
 * @todo This requires manual permission checks as it needs interesting things
 */

$season = MyRadio_Season::getInstance($_GET['show_season_id']);

CoreUtils::getTemplateObject()->setTemplate('table.twig')
        ->addVariable('tablescript', 'myury.scheduler.timeslotlist')
        ->addVariable('title', 'Episodes of '.$season->getMeta('title'))
        ->addVariable('tabledata', ServiceAPI::setToDataSource($season->getAllTimeslots()))
        ->render();