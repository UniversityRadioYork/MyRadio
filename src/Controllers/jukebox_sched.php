#!/usr/bin/php
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

require_once __DIR__.'/cli_common.php';

Config::$display_errors = true;

do {
  $tracks = null;
  while (empty($tracks)) {
    $playlist = iTones_Playlist::getPlaylistFromWeights();
    $tracks = $playlist->getTracks();
  }
  $track = $tracks[array_rand($tracks)];
} while (MyURY_TracklistItem::getIfPlayedRecently($track) or iTones_Utils::getIfQueued($track));

if (!iTones_Utils::requestTrack($track, 'main')) throw new MyURYException('Track Request Failed!');

exit(0);

print_r($track);

echo microtime(true) - $s;
echo "\n".Database::getInstance()->getCounter()."\n";
