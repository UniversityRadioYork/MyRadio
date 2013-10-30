<?php
/**
 * Controller for outputting a Datatable of Seasons within the specified Show
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130828
 * @package MyRadio_Scheduler
 * @todo This requires manual permission checks as it needs interesting things
 */

$show = MyRadio_Show::getInstance($_REQUEST['showid']);
$seasons = $show->getAllSeasons();

//This page is part of a joyride. We restart it if there's no seasons and this is their first Show.
if (sizeof(User::getInstance()->getShows()) === 1 && sizeof($seasons) === 1) {
  $_SESSION['joyride'] = 'first_show';
}

CoreUtils::getTemplateObject()->setTemplate('table.twig')
        ->addVariable('tablescript', 'myury.scheduler.seasonlist')
        ->addVariable('title', 'Seasons of '.$show->getMeta('title'))
        ->addVariable('tabledata', ServiceAPI::setToDataSource($seasons))
        ->render();