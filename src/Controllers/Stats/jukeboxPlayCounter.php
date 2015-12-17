<?php
/**
 * The most played jukebox tracks in the given timeframe.
 */
use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\ServiceAPI\MyRadio_TracklistItem;

$start = isset($_GET['rangesel-starttime']) ? strtotime($_GET['rangesel-starttime']) : time() - (86400 * 28);
$end = isset($_GET['rangesel-endtime']) ? strtotime($_GET['rangesel-endtime']) : time();

CoreUtils::getTemplateObject()->setTemplate('table_timeinput.twig')
    ->addVariable('title', 'Jukebox Track Play Counter')
    ->addVariable('tabledata', MyRadio_TracklistItem::getTracklistStatsForJukebox($start, $end))
    ->addVariable('tablescript', 'myradio.stats.jukeboxplaycounter')
    ->addVariable('starttime', CoreUtils::happyTime($start))
    ->addVariable('endtime', CoreUtils::happyTime($end))
    ->render();
