#!/usr/bin/php
<?php
/**
 * This is the Jukebox Scheduler Controller - when triggered, it will inject a track into the iTones playout queue.
 *
 * @uses    \Database
 * @uses    \CoreUtils
 */
use \MyRadio\iTones\iTones_Utils;
use \MyRadio\iTones\iTones_Playlist;
use \MyRadio\ServiceAPI\MyRadio_TracklistItem;

require_once __DIR__.'/root_cli.php';

$track = iTones_Utils::getTrackForJukebox();

echo $track->getPath() . "\n";
