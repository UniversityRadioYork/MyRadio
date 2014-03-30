#!/usr/bin/php
<?php
/**
 * This is the Jukebox Scheduler Controller - when triggered, it will inject a track into the iTones playout queue
 *
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130712
 * @package MyRadio_iTones
 * @uses \Database
 * @uses \CoreUtils
 */
require_once __DIR__.'/cli_common.php';

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
  } while ($track->getClean() === 'n' or
          (MyRadio_TracklistItem::getIfPlayedRecently($track) or iTones_Utils::getIfQueued($track)
          or !MyRadio_TracklistItem::getIfAlbumArtistCompliant($track))
          or $track->isBlacklisted());

echo $track->getPath()."\n";
