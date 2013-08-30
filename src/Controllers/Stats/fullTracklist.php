<?php
/**
 * Gets the full station tracklist - useful for PPL returns.
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130830
 * @package MyURY_Stats
 */

$start = isset($_GET['rangesel-starttime']) ? strtotime($_GET['rangesel-starttime']) : time()-(86400*28);
$end = isset($_GET['rangesel-endtime']) ? strtotime($_GET['rangesel-endtime']) : time();

$twig = CoreUtils::getTemplateObject()->setTemplate('table_timeinput.twig')
        ->addVariable('title', 'Station Tracklist History')
        ->addVariable('tablescript', 'myury.stats.fulltracklist')
        ->addVariable('starttime', CoreUtils::happyTime($start))
        ->addVariable('endtime', CoreUtils::happyTime($end));

$data = MyURY_TracklistItem::getTracklistForTime($start, $end);

if (sizeof($data) >= 50000) {
  $twig->addError('You have exceeded the maximum number of results for a single query. Please select a smaller timeframe and try again.');
}

$twig->addVariable('tabledata', $data);

unset($data);

$twig->render();