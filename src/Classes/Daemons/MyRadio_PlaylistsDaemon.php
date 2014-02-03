<?php

/**
 * Provides the MyRadio_PlaylistsDaemon class for MyRadio
 * @package MyRadio_Daemon
 */

/**
 * This Daemon updates the auto-generated iTones Playlists once an hour.
 *
 * @version 20130710
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @package MyRadio_Tracklist
 * @uses \Database
 *
 */
class MyRadio_PlaylistsDaemon extends MyRadio_Daemon
{
    public static function isEnabled()
    {
        return Config::$d_Playlists_enabled;
    }

    public static function run()
    {
        $hourkey = __CLASS__ . '_last_run_hourly';
        if (self::getVal($hourkey) > time() - 3500) {
            return;
        }

        self::updateMostPlayedPlaylist();
        self::updateNewestUploadsPlaylist();

        //Done
        self::setVal($hourkey, time());
    }

    private static function updateMostPlayedPlaylist()
    {
        $pobj = iTones_Playlist::getInstance('semantic-auto');
        $lockstr = $pobj->acquireOrRenewLock(null, MyRadio_User::getInstance(Config::$system_user));

        /**
         * Track play stats for last TWO weeks - not 120 days!!
         */
        $most_played = MyRadio_TracklistItem::getTracklistStatsForBAPS(time() - (86400 * 14));

        $playlist = array();
        for ($i = 0; $i < 20; $i++) {
            if (!isset($most_played[$i])) {
                break; //If there aren't that many, oh well.
            }
            $track = MyRadio_Track::getInstance($most_played[$i]['trackid']);
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
}
