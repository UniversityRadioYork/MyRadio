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
        self::updateLastFMGroupPlaylist();
        self::updateLastFMGeoPlaylist();
        self::updateLastFMTopPlaylist();
        self::updateLastFMHypePlaylist();

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
        //Get track statistics for every daytime window
        for ($i = 0; $i < 14; $i++) {
            $stats = MyRadio_TracklistItem::getTracklistStatsForBAPS(
                strtotime("6am -{$i} days"),
                strtotime("9pm -{$i} days")
            );
            //Accumulate the results
            foreach ($stats as $track) {
                if (!isset($most_played[$track['trackid']])) {
                    $most_played[$track['trackid']] = 0;
                }
                $most_played[$track['trackid']] += $track['num_plays'];
            }
        }

        //Sort array by play count
        arsort($most_played);
        //Get the trackids out
        $keys = array_keys($most_played);
        $playlist = [];
        //Take the top 20 from this list
        for ($i = 0; $i < 20; $i++) {
            //Last.FM's bit can be slow sometimes - make sure this doesn't expire
            $lockstr = $pobj->acquireOrRenewLock($lockstr, MyRadio_User::getInstance(Config::$system_user));
            $key = $keys[$i];
            if (!$key) {
                break; //If there aren't that many, oh well.
            }
            $track = MyRadio_Track::getInstance($key);
            //Ask last.fm for similar songs that are in our library
            $similar = $track->getSimilar();
            dlog('Found ' . sizeof($similar) . ' similar tracks for ' . $track->getID(), 4);
            //Add these to the playlist, along with the popular track
            $playlist[] = $track;
            for ($j = 0; $j < sizeof($similar); $j++)
            {
                $playlist[] = $similar[j];
            }
            //$playlist = array_merge($playlist, $similar);
        }
        //Actually update the playlist
        $pobj->setTracks(array_unique($playlist), $lockstr, null, MyRadio_User::getInstance(Config::$system_user));
        $pobj->releaseLock($lockstr);


        //Aaaand repeat
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
            $playlist[] = $track;
            for ($j = 0; $j < sizeof($similar); $j++)
            {
                $playlist[] = $similar[j];
            }
            //$playlist = array_merge($playlist, $similar);
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
    
    private static function updateLastFMGroupPlaylist()
    {
        $pobj = iTones_Playlist::getInstance('lastgroup-auto');
        $lockstr = $pobj->acquireOrRenewLock(null, MyRadio_User::getInstance(Config::$system_user));
        
        $data = json_decode(
                file_get_contents(
                    'https://ws.audioscrobbler.com/2.0/?method=group.getweeklytrackchart&api_key='
                    . Config::$lastfm_api_key
                    . '&group=' . Config::$lastfm_group
                    . '&format=json'
                ),
                true
            );
            
        $keys = [];
        
        foreach ($data['weeklytrackchart']['track'] as $r) {
            if ($r['playcount'] >= 2) {
                $c = MyRadio_Track::findByOptions(
                    [
                        'title' => $r['name'],
                        'artist' => $r['artist']['#text'],
                        'limit' => 1,
                        'digitised' => true
                    ]
                );
                if (!empty($c)) {
                $keys[] = $c[0]->getID();
                }
            }
        }
        
        $playlist = [];
        for ($i = 0; $i < sizeof($keys); $i++) {
            $lockstr = $pobj->acquireOrRenewLock($lockstr, MyRadio_User::getInstance(Config::$system_user));
            $key = $keys[$i];
            if (!$key) {
                break; //If there aren't that many, oh well.
            }
            $track = MyRadio_Track::getInstance($key);
            $similar = $track->getSimilar();
            dlog('Found ' . sizeof($similar) . ' similar tracks for ' . $track->getID(), 4);
            $playlist[] = $track;
            for ($j = 0; $j < sizeof($similar); $j++)
            {
                $playlist[] = $similar[j];
            }
            //$playlist = array_merge($playlist, $similar);
        }
        
        $pobj->setTracks(array_unique($playlist), $lockstr, null, MyRadio_User::getInstance(Config::$system_user));
        $pobj->releaseLock($lockstr);
        
    }
    
    private static function updateLastFMGeoPlaylist()
    {
        $pobj = iTones_Playlist::getInstance('lastgeo-auto');
        $lockstr = $pobj->acquireOrRenewLock(null, MyRadio_User::getInstance(Config::$system_user));
        
        $data = json_decode(
                file_get_contents(
                    'https://ws.audioscrobbler.com/2.0/?method=geo.getTopTracks&api_key='
                    . Config::$lastfm_api_key
                    . '&country=' . Config::$lastfm_geo
                    . '&limit=100&format=json'
                ),
                true
            );
            
        $playlist = [];
        
        foreach ($data['toptracks']['track'] as $r) {
            $c = MyRadio_Track::findByOptions(
                [
                    'title' => $r['name'],
                    'artist' => $r['artist']['name'],
                    'limit' => 1,
                    'digitised' => true
                ]
            );
            if (!empty($c)) {
                $playlist[] = $c[0];
            }
        }
        
        $pobj->setTracks(array_unique($playlist), $lockstr, null, MyRadio_User::getInstance(Config::$system_user));
        $pobj->releaseLock($lockstr);
        
    }
    
    private static function updateLastFMTopPlaylist()
    {
        $pobj = iTones_Playlist::getInstance('lasttop-auto');
        $lockstr = $pobj->acquireOrRenewLock(null, MyRadio_User::getInstance(Config::$system_user));
        
        $data = json_decode(
                file_get_contents(
                    'https://ws.audioscrobbler.com/2.0/?method=chart.getTopTracks&api_key='
                    . Config::$lastfm_api_key
                    . '&limit=100&format=json'
                ),
                true
            );
            
        $playlist = [];
        
        foreach ($data['tracks']['track'] as $r) {
            $c = MyRadio_Track::findByOptions(
                [
                    'title' => $r['name'],
                    'artist' => $r['artist']['name'],
                    'limit' => 1,
                    'digitised' => true
                ]
            );
            if (!empty($c)) {
                $playlist[] = $c[0];
            }
        }
        
        $pobj->setTracks(array_unique($playlist), $lockstr, null, MyRadio_User::getInstance(Config::$system_user));
        $pobj->releaseLock($lockstr);
        
    }
    
    private static function updateLastFMHypePlaylist()
    {
        $pobj = iTones_Playlist::getInstance('lasthype-auto');
        $lockstr = $pobj->acquireOrRenewLock(null, MyRadio_User::getInstance(Config::$system_user));
        
        $data = json_decode(
                file_get_contents(
                    'https://ws.audioscrobbler.com/2.0/?method=chart.getHypedTracks&api_key='
                    . Config::$lastfm_api_key
                    . '&limit=100&format=json'
                ),
                true
            );
            
        $playlist = [];
        
        foreach ($data['tracks']['track'] as $r) {
            $c = MyRadio_Track::findByOptions(
                [
                    'title' => $r['name'],
                    'artist' => $r['artist']['name'],
                    'limit' => 1,
                    'digitised' => true
                ]
            );
            if (!empty($c)) {
                $playlist[] = $c[0];
            }
        }
        
        $pobj->setTracks(array_unique($playlist), $lockstr, null, MyRadio_User::getInstance(Config::$system_user));
        $pobj->releaseLock($lockstr);
        
    }
    
}
