<?php

/*
 * This file provides the SIS_Tracklist class for MyRadio
 * @package MyRadio_SIS
 */

namespace MyRadio\SIS;

use \MyRadio\MyRadioException;
use \MyRadio\ServiceAPI\ServiceAPI;
use \MyRadio\ServiceAPI\MyRadio_TracklistItem;

/**
 * This class has helper functions for SIS tracklisting
 *
 * @version 20131011
 * @author Andy Durant <aj@ury.org.uk>
 * @package MyRadio_SIS
 */
class SIS_Tracklist extends ServiceAPI
{
    /**
     * Get track info tracklisted for a timeslot
     * @param  integer $timeslotid ID of timslot to get tracklist for
     * @param  integer $offset     tracklist logid to offset by
     * @return array   tracks in tracklist for the timeslot from the offset
     */
    public static function getTrackListing($timeslotid, $offset = 0)
    {
        $tracklist = MyRadio_TracklistItem::getTracklistForTimeslot($timeslotid, $offset);
        $tracks = [];
        foreach ($tracklist as $tracklistitem) {
            $track = $tracklistitem->getTrack();
            if (is_array($track)) {
                $tracks[] = [
                    'playtime' => $tracklistitem->getStartTime(),
                    'title' => $track['title'],
                    'artist' => $track['artist'],
                    'album' => $track['album'],
                    'trackid' => 'custom',
                    'id' => $tracklistitem->getID()
                 ];
            } else {
                $tracks[] = [
                    'playtime' => $tracklistitem->getStartTime(),
                    'title' => $track->getTitle(),
                    'artist' => $track->getArtist(),
                    'album' => $track->getAlbum()->getTitle(),
                    'trackid' => $track->getID(),
                    'id' => $tracklistitem->getID()
                 ];
            }
        }

        return $tracks;
    }

    /**
    * Adds a non-database track to the tracklist
    * @param  string $tname      track name
    * @param  string $artist     track artist
    * @param  string $album      track album
    * @param  string $source     tracklistig source
    * @param  int    $timeslotid ID of timeslot to tracklist to
    * @return none
    */
    public static function insertTrackNoRec($tname, $artist, $album, $source, $timeslotid)
    {
        self::$db->query('BEGIN');

        $audiologid = self::$db->fetchOne(
            'INSERT INTO tracklist.tracklist (source, timeslotid) VALUES ($1, $2) RETURNING audiologid',
            [$source, $timeslotid]
        );

        self::$db->query(
            'INSERT INTO tracklist.track_notrec (audiologid, artist, album, track) VALUES ($1, $2, $3, $4)',
            [$audiologid['audiologid'], $artist, $album, $tname]
        );

        self::$db->query('COMMIT');
    }

    public static function insertTrackRec(MyRadio_Track $track, $source, $timeslotid)
    {
        self::$db->query('BEGIN');

        $audiologid = self::$db->fetchOne(
            'INSERT INTO tracklist.tracklist (source, timeslotid)
            VALUES ($1, $2) RETURNING audiologid',
            [$source, $timeslotid]
        );

        self::$db->query(
            'INSERT INTO tracklist.track_rec (audiologid, recordid, trackid)
            VALUES ($1, $2, $3)',
            [$audiologid['audiologid'], $track->getAlbum()->getID(), $track->getID()]
        );

        self::$db->query('COMMIT');
        return true;
    }

    public static function markTrackDeleted($tracklistid)
    {
        self::$db->query(
            'UPDATE tracklist.tracklist SET state = \'d\'
            WHERE audiologid = $1',
            [$tracklistid]
        );
        if (self::$db->numRows() !== 1) {
            throw new MyRadioException('Failed to delete tracklistitem ' . $tracklistid, 500);
        }
    }
}
