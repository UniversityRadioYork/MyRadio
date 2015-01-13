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

// Get public shows (type 1) from this term only (true)
$shows = MyRadio_Show::getAllShows(1, true);
CoreUtils::getTemplateObject()->setTemplate('table.twig')
    ->addVariable('title', 'All Shows')
    ->addVariable('tabledata', ServiceAPI::setToDataSource($shows))
    ->addVariable('tablescript', 'myury.scheduler.showlist')
    ->render();
