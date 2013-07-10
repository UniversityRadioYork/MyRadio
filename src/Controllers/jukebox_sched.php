#!/usr/local/bin/php
<?php
/**
 * This is the Jukebox Scheduler Controller - when triggered, it will inject a track into the iTones playout queue
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130709
 * @package MyURY_iTones
 * @uses \Database
 * @uses \CoreUtils
 */
$s = microtime(true);
//Set up environment
date_default_timezone_get('Europe/London');
ini_set('include_path', str_replace('Controllers', '', __DIR__) . ':' . ini_get('include_path'));
define('SHIBBOBLEH_ALLOW_READONLY', true);
require_once 'Classes/MyURY/CoreUtils.php';
require_once 'Classes/Config.php';
require_once 'Classes/MyURYEmail.php';
require 'Models/Core/api.php';

Config::$display_errors = true;

do {
  $tracks = null;
  while (empty($tracks)) {
    $playlist = iTones_Playlist::getPlaylistFromWeights();
    $tracks = $playlist->getTracks();
  }
  $track = $tracks[array_rand($tracks)];
} while (!MyURY_TracklistItem::getIfPlayedRecently($track));

if (!iTones_Utils::requestTrack($track)) throw new MyURYException('Track Request Failed!');

exit(0);

print_r($track);

echo microtime(true) - $s;
echo "\n".Database::getInstance()->getCounter()."\n";
