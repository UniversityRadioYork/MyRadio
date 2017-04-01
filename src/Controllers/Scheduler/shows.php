<?php
/**
 * @todo Proper Documentation
 */
use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\ServiceAPI\MyRadio_Show;

$all = (isset($_REQUEST['all']) && $_REQUEST['all'] === 'true');
// Get public shows (type 1) and get all shows (!$all === false) or just this term's (true)
$shows = MyRadio_Show::getAllShows(1, !$all);
CoreUtils::getTemplateObject()->setTemplate('table.twig')
    ->addVariable('title', $all ? 'All Shows' : "This Term's Shows")
    ->addVariable('tabledata', CoreUtils::setToDataSource($shows))
    ->addVariable('tablescript', 'myradio.scheduler.showlist')
    ->render();
