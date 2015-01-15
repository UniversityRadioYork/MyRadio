<?php
/**
 *
 * @todo Proper Documentation
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 21072012
 * @package MyRadio_Scheduler
 */

use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\ServiceAPI\ServiceAPI;
use \MyRadio\ServiceAPI\MyRadio_Show;

$all = (isset($_REQUEST["all"]) && $_REQUEST["all"] === 'true');
// Get public shows (type 1) and get all shows (!$all === false) or just this term's (true)
$shows = MyRadio_Show::getAllShows(1, !$all);
CoreUtils::getTemplateObject()->setTemplate('table.twig')
    ->addVariable('title', 'All Shows')
    ->addVariable('tabledata', ServiceAPI::setToDataSource($shows))
    ->addVariable('tablescript', 'myury.scheduler.showlist')
    ->render();
