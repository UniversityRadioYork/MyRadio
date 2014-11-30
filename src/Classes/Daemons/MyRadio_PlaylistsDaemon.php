<?php

/**
 * Provides the MyRadio_PlaylistsDaemon class for MyRadio
 * @package MyRadio_Daemon
 */

namespace MyRadio\Daemons;

use \MyRadio\Config;
use \MyRadio\iTones\iTones_Playlist;
use \MyRadio\ServiceAPI\MyRadio_User;
use \MyRadio\ServiceAPI\MyRadio_Track;
use \MyRadio\ServiceAPI\MyRadio_TracklistItem;
use \MyRadio\NIPSWeb\NIPSWeb_AutoPlaylist;

/**
 * This Daemon updates the auto-generated iTones Playlists once an hour.
 *
 * @version 20130710
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @package MyRadio_Tracklist
 * @uses \Database
 *
 */
class MyRadio_PlaylistsDaemon extends \MyRadio\MyRadio\MyRadio_Daemon
{
    public static function isEnabled()
    {
        return Config::$d_Playlists_enabled;
    }

    public static function run($force = false)
    {
        $hourkey = __CLASS__ . '_last_run_hourly';
        if (!$force && self::getVal($hourkey) > time() - 3500) {
            return;
        }

        self::updateMostPlayedPlaylist();
        self::updateNewestUploadsPlaylist();
        self::updateRandomTracksPlaylist();

        //Done
        self::setVal($hourkey, time());
    }

    private static function updateMostPlayedPlaylist()
    {
        $pobj = iTones_Playlist::getInstance('semantic-auto');
        $lockstr = $pobj->acquireOrRenewLock(null, MyRadio_User::getInstance(Config::$system_user));

        /**
         * Daytime Track play stats for last 14 days
         */
        $most_played = [];
        for ($i = 0; $i < 14; $i++) {
            $stats = MyRadio_TracklistItem::getTracklistStatsForBAPS(
                strtotime("6am -{$i} days"),
                strtotime("9pm -{$i} days")
            );
            foreach ($stats as $track) {
                if (!isset($most_played[$track['trackid']])) {
                    $most_played[$track['trackid']] = 0;
                }
                $most_played[$track['trackid']] += $track['num_plays'];
            }
        }

        arsort($most_played);
        $keys = array_keys($most_played);
        $playlist = [];
        for ($i = 0; $i < 20; $i++) {
            $lockstr = $pobj->acquireOrRenewLock($lockstr, MyRadio_User::getInstance(Config::$system_user));
            $key = $keys[$i];
            if (!$key) {
                break; //If there aren't that many, oh well.
            }
            $track = MyRadio_Track::getInstance($key);
            $similar = $track->getSimilar();
            dlog('Found ' . sizeof($similar) . ' similar tracks for ' . $track->getID(), 4);
            $playlist = array_merge($playlist, $similar);
            $playlist[] = $track;
        }
        $pobj->setTracks(array_unique($playlist), $lockstr, null, MyRadio_User::getInstance(Config::$system_user));
        $pobj->releaseLock($lockstr);

        $pobj = iTones_Playlist::getInstance('semantic-spec');
        $lockstr = $pobj->acquireOrRenewLock(null, MyRadio_User::getInstance(Config::$system_user));

        /**
         * Specialist Track play stats for last 14 days
         */
        $most_played = [];
        for ($i = 0; $i < 14; $i++) {
            $j = $i + 1;
            $stats = MyRadio_TracklistItem::getTracklistStatsForBAPS(
                strtotime("9pm -{$j} days"),
                strtotime("6am -{$i} days")
            );
            foreach ($stats as $track) {
                if (!isset($most_played[$track['trackid']])) {
                    $most_played[$track['trackid']] = 0;
                }
                $most_played[$track['trackid']] += $track['num_plays'];
            }
        }

        arsort($most_played);
        $keys = array_keys($most_played);

        $playlist = [];
        for ($i = 0; $i < 20; $i++) {
            $lockstr = $pobj->acquireOrRenewLock($lockstr, MyRadio_User::getInstance(Config::$system_user));
            $key = $keys[$i];
            if (!$key) {
                break; //If there aren't that many, oh well.
            }
            $track = MyRadio_Track::getInstance($key);
            $similar = $track->getSimilar();
            dlog('Found ' . sizeof($similar) . ' similar tracks for ' . $track->getID(), 4);
            $playlist = array_merge($playlist, $similar);
            $playlist[] = $track;
        }

        $pobj->setTracks(array_unique($playlist), $lockstr, null, MyRadio_User::getInstance(Config::$system_user));
        $pobj->releaseLock($lockstr);
    }

    private static function updateNewestUploadsPlaylist()
    {
        $pobj = iTones_Playlist::getInstance('newest-auto');
        $lockstr = $pobj->acquireOrRenewLock(null, MyRadio_User::getInstance(Config::$system_user));

        $newest_tracks = NIPSWeb_AutoPlaylist::findByName('Newest Tracks')->getTracks();

        $pobj->setTracks($newest_tracks, $lockstr, null, MyRadio_User::getInstance(Config::$system_user));
        $pobj->releaseLock($lockstr);
    }


    private static function updateRandomTracksPlaylist()
    {
        $pobj = iTones_Playlist::getInstance('random-auto');
        $lockstr = $pobj->acquireOrRenewLock(null, MyRadio_User::getInstance(Config::$system_user));

        $random_tracks = NIPSWeb_AutoPlaylist::findByName('Random Tracks')->getTracks();

        $pobj->setTracks($random_tracks, $lockstr, null, MyRadio_User::getInstance(Config::$system_user));
        $pobj->releaseLock($lockstr);
    }
}
