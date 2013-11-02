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
					'album' => $track->getAlbum(),
					'id' => $tracklistitem->getID()
				 );
			}
		}
		return $tracks;
	}
}