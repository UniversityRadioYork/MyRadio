#!/usr/bin/php
<?php
/**
 * This is the Jukebox Scheduler Controller - when triggered, it will inject a track into the iTones playout queue
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130712
 * @package MyURY_iTones
 * @uses \Database
 * @uses \CoreUtils
 */
$s = microtime(true);

require_once __DIR__.'/cli_common.php';

Config::$display_errors = true;

//Do an extra check here for duplicate tracks in the queue - the seem to manage to weasel themselves in somehow.
//I think it may be this script running more than once or something similar.
$count = iTones_Utils::removeDuplicateItemsInQueues();

if ($count !== 0) trigger_error("Removed $count duplicate items in queues.");

$current_queue_length = sizeof(iTones_Utils::getTracksInQueue('main'));
if ($current_queue_length > 5) exit(0); //There's enough there for now, I think.

do {
  //We limit the number of attempts (to 10 ^ number of tracks needed), after which we'll try again later
  $i = 10^(5-$current_queue_length);
  do {
    $tracks = null;
    //Pick a playlist at random, until we find one that actually has tracks
    while (empty($tracks)) {
      $playlist = iTones_Playlist::getPlaylistFromWeights();
      $tracks = $playlist->getTracks();
    }
    //Pick a track at random from the playlist
    $track = $tracks[array_rand($tracks)];
 
  //If this track has been played recently or is currently queued, we can't play it. Try again.
  } while ((MyURY_TracklistItem::getIfPlayedRecently($track) or iTones_Utils::getIfQueued($track)
          or !MyURY_TracklistItem::getIfAlbumArtistCompliant($track)) && --$i > 0);

  //Actually send the telnet request, if we didn't run out of tries.
  if ($i > 0) {
    if (!iTones_Utils::requestTrack($track, 'main')) throw new MyURYException('Track Request Failed!');
    $current_queue_length++;
  }

//If the queue length is less than 3, then run again to pad it out some more.
} while ($current_queue_length < 3);

exit(0);

//Debug stuff - uncomment the exit to see timing
print_r(iTones_Utils::$ops);

echo microtime(true) - $s;
echo "\n".Database::getInstance()->getCounter()."\n";
