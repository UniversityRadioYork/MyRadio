<?php
/**
 * This file provides the MyRadio_BAPSSyncDaemon class for MyRadio.
 */
namespace MyRadio\Daemons;

use MyRadio\Config;
use MyRadio\Database;
use MyRadio\iTones\iTones_Playlist;
use MyRadio\NIPSWeb\NIPSWeb_BAPSUtils;
use MyRadio\NIPSWeb\NIPSWeb_ManagedPlaylist;

/**
 * The BAPSSync Daemon will scan the Show Planner playlists, and put them into BAPS.
 */
class MyRadio_BAPSSyncDaemon extends \MyRadio\MyRadio\MyRadio_Daemon
{
    /**
     * If this method returns true, the Daemon host should run this Daemon. If it returns false, it must not.
     * It is currently disabled because it hasn't actuall finished being ported from NIPSWeb 1.
     *
     * @return bool
     */
    public static function isEnabled()
    {
        return Config::$d_BAPSSync_enabled;
    }

    /**
     * Once an hour, this takes each of the NIPSWebs Managed and Resource Lists,
     * and converts them into BAPS Recommended Audio shows.
     * This is a Legacy tool to support the old BAPS system. DO NOT TOUCH. BAPS IS SILLY.
     */
    public static function run()
    {
        $hourkey = __CLASS__.'_last_run_hourly';
        if (self::getVal($hourkey) > time() - 3500) {
            return;
        }

        $special_date = '2034-05-06 07:08:09'; //All shows created by this are identified by this time.
        $db = Database::getInstance();

        //This needs to appear atomic to end users
        $db->query('BEGIN');
        $db->query('DELETE FROM public.baps_show WHERE broadcastdate=$1', [$special_date]);
        //Start with the Jukebox Playlists
        foreach (iTones_Playlist::getAlliTonesPlaylists() as $list) {
            /*
             * Create the playlist. '61' is system, which appears to be how BAPS chooses what shows are recommended listening
             */
            $r = $db->fetchColumn(
                'INSERT INTO public.baps_show (userid, name, broadcastdate, viewable)
                VALUES (61, $1, $2, true) RETURNING showid',
                [$list->getTitle(), $special_date]
            );
            $showid = $r[0];

            if (empty($r)) {
                return;
            }

            /*
             * Create a single channel for the show, containing the items
             */
            $r = $db->fetchOne(
                'INSERT INTO public.baps_listing (showid, name, channel) VALUES ($1, $2, 0) RETURNING listingid',
                [$showid, $list->getTitle()]
            );
            $listingid = $r['listingid'];

            if (empty($r)) {
                return;
            }

            $i = 0;

            foreach ($list->getTracks() as $track) {
                ++$i;
                $libraryitemid = NIPSWeb_BAPSUtils::getTrackDetails($track->getID(), $track->getAlbum()->getID())['libraryitemid'];
                pg_query_params(
                    'INSERT INTO public.baps_item (listingid, name1, name2, position, libraryitemid)
                    VALUES ($1, $2, $3, $4, $5)',
                    [$listingid, $track->getTitle(), $track->getArtist(), $i, $libraryitemid]
                );
                echo pg_last_error();
            }
        }

        //And now the Aux Resource Playlists
        foreach (NIPSWeb_ManagedPlaylist::getAllManagedPlaylists() as $list) {
            /*
             * Create the playlist. '61' is system, which appears to be how BAPS chooses what shows are recommended listening
             */
            $r = pg_fetch_row(
                pg_query_params(
                    'INSERT INTO public.baps_show (userid, name, broadcastdate, viewable)
                VALUES (61, \'Managed::\'||$1, $2, true) RETURNING showid',
                    [$list->getTitle(), $special_date]
                )
            );

            $showid = $r[0];
            echo pg_last_error();
            if (!$r) {
                return;
            }

            /*
             * Create a single channel for the show, containing the items
             */
            $r = pg_fetch_row(
                pg_query_params(
                    'INSERT INTO public.baps_listing (showid, name, channel) VALUES
                ($1, $2, 0) RETURNING listingid',
                    [$showid, $list->getTitle()]
                )
            );

            $listingid = $r[0];
            echo pg_last_error();
            if (!$r) {
                return;
            }

            $i = 0;
            foreach ($list->getItems() as $item) {
                $track = NIPSWeb_BAPSUtils::getFileItemFromManagedID($item->getID());
                ++$i;
                pg_query_params(
                    'INSERT INTO public.baps_item (listingid, name1, name2, position, fileitemid)
                    VALUES ($1, $2, $3, $4, $5)',
                    [$listingid, $item->getTitle(), '', $i, $track]
                );
                echo pg_last_error();
            }
        }

        echo pg_last_error();
        pg_query('COMMIT');

        self::setVal($hourkey, time());
    }
}
