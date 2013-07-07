<?php
/**
 * The most listened to timeslots this academic year
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130626
 * @package MyURY_Stats
 */
require 'Views/bootstrap.php';

$start = isset($_GET['rangesel-starttime']) ? strtotime($_GET['rangesel-starttime']) : null;
$end = isset($_GET['rangesel-endtime']) ? strtotime($_GET['rangesel-endtime']) : null;

$twig->setTemplate('table_timeinput.twig')
        ->addVariable('title', 'Jukebox Track Play Counter')
        ->addVariable('heading', 'Jukebox Track Play Counter')
        ->addVariable('tabledata', MyURY_TracklistItem::getTracklistStatsForJukebox($start, $end))
        ->addVariable('tablescript', 'myury.stats.jukeboxplaycounter')
        ->render();