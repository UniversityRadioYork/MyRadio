<?php
/**
 *
 * @todo Proper Documentation


 * @package MyRadio_Scheduler
 */

use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\ServiceAPI\ServiceAPI;
use \MyRadio\ServiceAPI\MyRadio_Show;

$all = (isset($_REQUEST["all"]) && $_REQUEST["all"] === 'true');
// Get public shows (type 1) and get all shows (!$all === false) or just this term's (true)
$shows = MyRadio_Show::getAllShows(1, !$all);
CoreUtils::getTemplateObject()->setTemplate('table.twig')
    ->addVariable('title', $all ? 'All Shows': 'This Term\'s Shows')
    ->addVariable('tabledata', ServiceAPI::setToDataSource($shows))
    ->addVariable('tablescript', 'myury.scheduler.showlist')
    ->render();
