<?php

/*
 * This file provides the NIPSWeb_BAPSUtils class for MyRadio
 * @package MyRadio_NIPSWeb
 */

namespace MyRadio\NIPSWeb;

use MyRadio\Config;
use MyRadio\MyRadioException;
use MyRadio\MyRadio\CoreUtils;
use MyRadio\ServiceAPI\MyRadio_Timeslot;

/**
 * This class has helper functions for saving Show Planner show informaiton into legacy BAPS Show layout.
 */
class NIPSWeb_BAPSUtils extends \MyRadio\ServiceAPI\ServiceAPI
{
    public static function getBAPSShowIDFromTimeslot(MyRadio_Timeslot $timeslot)
    {
        $result = self::$db->fetchColumn(
            'SELECT showid FROM baps_show
            WHERE externallinkid=$1 LIMIT 1',
            [$timeslot->getID()]
        );

        if (empty($result)) {
            //No match. Create a show
            $result = self::$db->fetchColumn(
                'INSERT INTO baps_show (userid, name, broadcastdate, externallinkid, viewable)
                VALUES (4, $1, $2, $3, true) RETURNING showid',
                [
                    $timeslot->getMeta('title')
                    .'-'
                    .$timeslot->getID(),
                    CoreUtils::getTimestamp($timeslot->getStartTime()),
                    $timeslot->getID(),
                ]
            );
        }

        return (int) $result[0];
    }

    /**
     * Takes a BAPS ShowID, and gets the channel references for the show
     * If a listing for one or more channels does not exist, this method
     * creates them automatically.
     *
     * @param int $showid The BAPS show id
     *
     * @return bool|array An array of BAPS Channels, or false on failure
     */
    public static function getListingsForShow($showid)
    {
        $listings = self::$db->fetchAll(
            'SELECT * FROM baps_listing
            WHERE showid=$1 ORDER BY channel ASC',
            [(int) $showid]
        );

        if (!$listings) {
            $listings = [];
        }

        if (sizeof($listings) === 3) {
            //There's already three, there's no need to check which exist
            return $listings;
        }

        /*
         * @todo Wow I was lazy here.
         */
        $channels = [false, false, false];

        //Flag existing channels as, well, existing
        foreach ($listings as $listing) {
            $channels[$listing['channel']] = true;
        }

        //Go over the channels and create the nonexistent ones
        $change = false;
        foreach ($channels as $channel => $exists) {
            if (!$exists) {
                self::$db->query(
                    'INSERT INTO baps_listing (showid, name, channel)
                    VALUES ($1, \'Channel '.$channel.'\', $2)',
                    [$showid, $channel]
                );
                $change = true;
            }
        }
        //If the show definition has changed, recurse this method
        if ($change) {
            return self::getListingsForShow($showid);
        } else {
            return $listings;
        }
    }

    public static function saveListingsForTimeslot(MyRadio_Timeslot $timeslot)
    {
        //Get the timeslot's show plan
        $tracks = $timeslot->getShowPlan();

        //Get the listings related to the show
        $showid = self::getBAPSShowIDFromTimeslot($timeslot);
        $listings = self::getListingsForShow($showid);

        //Start a transaction for this change
        self::$db->query('BEGIN');

        foreach ($listings as $listing) {
            //Delete the old format
            self::$db->query('DELETE FROM baps_item WHERE listingid=$1', [$listing['listingid']], true);
            //Add each new entry
            $position = 1;
            //if the listing isn't empty then write the tracks that are in there
            if (isset($tracks[$listing['channel']])) {
                foreach ($tracks[$listing['channel']] as $track) {
                    switch ($track['type']) {
                        case 'central':
                            $file = self::getTrackDetails($track['trackid'], $track['album']['recordid']);
                            self::$db->query(
                                'INSERT INTO baps_item (listingid, position, libraryitemid, name1, name2)
                                VALUES ($1, $2, $3, $4, $5)',
                                [
                                $listing['listingid'],
                                $position,
                                $file['libraryitemid'],
                                $file['title'],
                                $file['artist'],
                                ],
                                true
                            );

                            break;
                        case 'aux':
                            //Get the LegacyDB ID of the file
                            $fileitemid = self::getFileItemFromManagedID($track['managedid']);
                            self::$db->query(
                                'INSERT INTO baps_item (listingid, position, fileitemid, name1)
                                VALUES ($1, $2, $3, $4)',
                                [
                                (int) $listing['listingid'],
                                (int) $position,
                                (int) $fileitemid,
                                $track['title'],
                                ],
                                true
                            );

                            break;
                        default:
                            throw new MyRadioException('What do I even with this item?');
                    }
                    ++$position;
                }
            }
        }

        self::$db->query('COMMIT');
    }

    /**
     * Gets the title, artist, and BapsWeb libraryitemid of a track.
     *
     * @param int $trackid  The Track ID from the rec database
     * @param int $recordid The Record ID from the rec database
     *
     * @return bool|array False on failure, or an array of the above
     */
    private static function getTrackDetails($trackid, $recordid)
    {
        $trackid = (int) $trackid;
        $recordid = (int) $recordid;
        $result = self::$db->fetchOne(
            'SELECT title, artist, libraryitemid
            FROM rec_track, baps_libraryitem
            WHERE rec_track.trackid = baps_libraryitem.trackid
            AND rec_track.trackid=$1 LIMIT 1',
            [$trackid]
        );

        if (empty($result)) {
            //Create the baps_libraryitem and recurse. pg_query_params doesn't like this...
            $result = self::$db->query(
                'INSERT INTO baps_libraryitem
                (trackid, recordid) VALUES ($1, $2)',
                [$trackid, $recordid]
            );

            return self::getTrackDetails($trackid, $recordid);
        }

        return $result;
    }

    /**
     * Returns the FileItemID from a ManagedItemID.
     */
    public static function getFileItemFromManagedID($auxid)
    {
        $item = NIPSWeb_ManagedItem::getInstance($auxid);

        $legacy_path = Config::$music_smb_path.'\\membersmusic\\fileitems\\'.self::sanitisePath($item->getTitle()).'_'.$auxid.'.mp3';
        //Make a hard link if it doesn't exist
        $ln_path = Config::$music_central_db_path.'/membersmusic/fileitems/'.self::sanitisePath($item->getTitle()).'_'.$auxid.'.mp3';

        if (!file_exists($ln_path)) {
            if (!@link($item->getPath('mp3'), $ln_path)) {
                trigger_error('Could not link '.$item->getPath('mp3').' to '.$ln_path);
            }
        }
        $id = self::getFileItemFromPath($legacy_path);

        if (!$id) {
            //Create it
            $r = self::$db->fetchColumn('INSERT INTO public.baps_fileitem (filename) VALUES ($1) RETURNING fileitemid', [$legacy_path]);

            return $r[0];
        }

        return $id;
    }

    public static function linkCentralLists(NIPSWeb_ManagedItem $item)
    {
        if (in_array($item->getFolder(), ['jingles', 'beds', 'adverts']) !== false) {
            //Make a hard link if it doesn't exist
            $ln_path = Config::$music_central_db_path.'/membersmusic/'.$item->getFolder().'/'.self::sanitisePath($item->getTitle()).'.mp3';

            if (!file_exists($ln_path)) {
                if (!@link($item->getPath(), $ln_path)) {
                    trigger_error('Could not link '.$item->getPath().' to '.$ln_path);
                }
            }
        }
    }

    /**
     * Returns the ID of an item in the auxillary database based on its samba path.
     *
     * @param string $path The Samba Share location of the file to search for
     *
     * @return bool|int false on error or non existent, fileitemid otherwise
     */
    public static function getFileItemFromPath($path)
    {
        $result = self::$db->fetchColumn(
            'SELECT fileitemid FROM baps_fileitem
            WHERE filename=$1 LIMIT 1',
            [$path]
        );

        if (empty($result)) {
            return false;
        }

        return (int) $result[0];
    }

    /**
     * Ensure a string can be used as a filename.
     *
     * @param type $file
     */
    public static function sanitisePath($file)
    {
        return trim(preg_replace("/[^0-9^a-z^,^_^.^\(^\)^-^ ]/i", '', str_replace('..', '.', $file)));
    }
}
