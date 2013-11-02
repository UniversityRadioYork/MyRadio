<?php

/*
 * This file provides the SIS_Tracklist class for MyRadio
 * @package MyRadio_SIS
 */

/**
 * This class has helper functions for SIS tracklisting
 * 
 * @version 20131011
 * @author Andy Durant <aj@ury.org.uk>
 * @package MyRadio_SIS
 */
class SIS_Tracklist extends ServiceAPI {

	/**
	 * Get track info tracklisted for a timeslot
	 * @param  integer  $timeslotid ID of timslot to get tracklist for
	 * @param  integer  $offset     tracklist logid to offset by
	 * @return array                tracks in tracklist for the timeslot from the offset
	 */
	public static function getTrackListing($timeslotid, $offset = 0) {
		$tracklist = MyRadio_TracklistItem::getTracklistForTimeslot($timeslotid, $offset);
		$tracks = array();
		foreach ($tracklist as $tracklistitem) {
			$track = $tracklistitem->getTrack();
			if (is_array($track)) {
				$tracks[] = array(
					'playtime' => $tracklistitem->getStartTime(),
					'title' => $track['title'],
					'artist' => $track['artist'],
					'album' => $track['album'],
					'id' => $tracklistitem->getID()
				 );
			}
			else {
				$tracks[] = array(
					'playtime' => $tracklistitem->getStartTime(),
					'title' => $track->getTitle(),
					'artist' => $track->getArtist(),
					'album' => $track->getAlbum()->getTitle(),
					'id' => $tracklistitem->getID()
				 );
			}
		}
		return $tracks;
	}

	/**
	 * Adds a non-database track to the tracklist
	 * @param  string $tname      track name
	 * @param  string $artist     track artist
	 * @param  string $album      track album
	 * @param  time   $time       php time
	 * @param  string $source     tracklistig source
	 * @param  int    $timeslotid ID of timeslot to tracklist to
	 * @return none
	 */
	 public static function insertTrackNoRec($tname, $artist, $album, $time, $source, $timeslotid) {
	 	$tname = pg_escape_string($tname);
		$artist = pg_escape_string($artist);
		$album = pg_escape_string($album);
		$source = pg_escape_string($source);
		$timeslotid = pg_escape_string($timeslotid);

		self::$db->query('BEGIN');
		$audiologid = self::$db->query('INSERT INTO tracklist.tracklist (source, timeslotid) 
			VALUES ($1, $2) RETURNING audiologid',
			array($source, $timeslotid));

		$r = self::$db->query('INSERT INTO tracklist.track_notrec (audiologid, artist, album, track) 
			VALUES ($1, $2, $3, $4)',
			array($audiologid[0], $artist, $album, $tname));

		if (!$r) {
			$query = "ROLLBACK;";
		}
		else {
			$query = "COMMIT";
		}
		self::$db->query($query);
	}

	/**
	 * checks if track is in database
	 * @param  string $artist track artist
	 * @param  string $album  track album
	 * @param  string $tname  track name
	 * @return array          result of db query
	 */
	public static function checkTrackOK($artist, $album, $tname) {
	 	$artist = pg_escape_string($artist);
	 	$album = pg_escape_string($album);
	 	$tname = pg_escape_string($tname);

	 	$result = self::$db->query('SELECT DISTINCT trk.title AS track, rec.title AS album, trk.artist AS artist, trk.trackid AS trackid, rec.recordid AS recordid
	 		FROM rec_track trk
	 		INNER JOIN rec_record rec ON ( rec.recordid = trk.recordid )
	 		WHERE trk.artist ILIKE $1
	 		AND rec.title ILIKE $2
	 		AND trk.title ILIKE $3
	 		ORDER BY trk.title ASC LIMIT 10',
	 		array($artist, $album, $tname));
	 	return $result;
	}

	public function insertTrackRec($trackid, $recid, $time, $source, $timeslotid) {
		$trackid = pg_escape_string($trackid);
		$recid = pg_escape_string($recid);
		$time = pg_escape_string($time);
		$source = pg_escape_string($source);
		$timeslotid = pg_escape_string($timeslotid);
		
		self::$db->query('BEGIN');

		$audiologid = self::$db->query('INSERT INTO tracklist.tracklist (source, timeslotid) 
			VALUES ($1, $2) RETURNING audiologid',
			array($source, $timeslotid));

		$audiologid = pg_fetch_row($audiologid);

		$r = self::$db->query('INSERT INTO tracklist.track_rec (audiologid, recordid, trackid) 
			VALUES ($1, $2, $3)',
			array($audiologid[0], $recid, $trackid));

		if (!$r){
			$query = "ROLLBACK;";
		}
		else {
			$query = "COMMIT";
		}
		self::$db->query($query);
	}
	
}