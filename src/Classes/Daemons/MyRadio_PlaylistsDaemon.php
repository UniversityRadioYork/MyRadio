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
    private static $locks = [];

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

    private static function playlistGenPrepare($playlistid)
    {
        $playlist = iTones_Playlist::getInstance($playlistid);
        $lock = $playlist->acquireOrRenewLock(null, MyRadio_User::getInstance(Config::$system_user));
        self::$locks[$playlistid] = [
            $playlist,
            $lock
        ];
        if ($lock === false) {
            dlog('ERROR updating playlist: could not get lock on ' . $playlistid, 3);
        }
        return $lock !== false;
    }

    private static function playlistGenCommit($playlistid, $data)
    {
        if (empty(self::$locks[$playlistid][1])) {
            dlog('ERROR updating playlist: lock not acquired ' . $playlistid, 3);
            return false;
        }

        if (empty($data)) {
            dlog('Warning: Saving empty playlist ' . $playlistid, 3);
        } else {
            dlog('Saving ' . sizeof($data) . ' items to ' . $playlistid, 5);
        }

        self::$locks[$playlistid][0]->setTracks(
            $data,
            self::$locks[$playlistid][1],
            null,
            MyRadio_User::getInstance(Config::$system_user)
        );
        self::$locks[$playlistid][0]->releaseLock(self::$locks[$playlistid][1]);
        self::$locks[$playlistid][1] = false;
    }

    /**
    * @param $data array of ['title': title, 'artist': artist, 'count': value]
    * Where count is only required if $threshold is set
    * @param $limit int The maximum number of matched tracks to return (0 == no limit)
    * @param $threshold int The minimum value of `count` to consider
    * @param $include_similar bool Whether to include similar tracks or just the track itself
    */
    private static function dataSimilarIterator(
        $data,
        $limit = 0,
        $threshold = null,
        $include_similar = true
    ) {
        $playlist = [];
        $count = 0;
        foreach ($data as $item) {
            if ($threshold === null or $item['count'] >= $threshold) {
                $similar = self::getTrackAndSimilar(
                    $item['title'],
                    $item['artist'],
                    $include_similar
                );

                if (!empty($similar)) {
                    $playlist = array_merge($playlist, $similar);
                    $count++;
                    if ($limit !== 0 && $count >= $limit) {
                        break;
                    }
                }
            }
        }

        return $playlist;
    }

    private static function getTrackAndSimilar($title, $artist, $include_similar)
    {
        //Try to find an exact match
        $c = MyRadio_Track::findByOptions(
            [
                'title' => $title,
                'artist' => $artist,
                'limit' => 1,
                'digitised' => true,
                'precise' => true
            ]
        );

        //Try and find a not-so-exact match
        if (empty($c)) {
            $c = MyRadio_Track::findByOptions(
                [
                    'title' => $title,
                    'artist' => $artist,
                    'limit' => 1,
                    'digitised' => true,
                    'precise' => false
                ]
            );
        }

        //Whelp, nothing
        if (empty($c)) {
            return [];
        } elseif ($include_similar) {
            //Whoop, something!
            $similar = $c[0]->getSimilar();
            dlog('Found ' . sizeof($similar) . ' similar tracks for ' . $c[0]->getTitle() . ' - ' .$c[0]->getArtist(), 4);
            // Unshift edits array in-place
            array_unshift($similar, $c[0]);
            return $similar;
        } else {
            return $c;
        }
    }

    private static function trackCountListGenerator($tracks)
    {
        //Sort array by play count
        arsort($tracks);
        //Get the trackids out
        $keys = array_keys($tracks);
        $playlist = [];
        //Take the top 20 from this list
        for ($i = 0; $i < min(20, sizeof($tracks)); $i++) {
            $key = $keys[$i];
            $track = MyRadio_Track::getInstance($key);
            //Ask last.fm for similar songs that are in our library
            $similar = $track->getSimilar();
            dlog('Found ' . sizeof($similar) . ' similar tracks for ' . $track->getTitle() . ' - ' .$track->getArtist(), 4);
            //Add these to the playlist, along with the popular track
            $playlist[] = $track;
            $playlist = array_merge($playlist, $similar);
        }

        return $playlist;
    }

    private static function updateMostPlayedPlaylist()
    {
        if (self::playlistGenPrepare('semantic-auto')) {
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

            self::playlistGenCommit(
                'semantic-auto',
                self::trackCountListGenerator($most_played)
            );
        }


        //Aaaand repeat
        if (self::playlistGenPrepare('semantic-spec')) {
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

            self::playlistGenCommit(
                'semantic-spec',
                self::trackCountListGenerator($most_played)
            );
        }
    }

    private static function updateNewestUploadsPlaylist()
    {
        if (!self::playlistGenPrepare('newest-auto')) {
            return;
        }

        self::playlistGenCommit(
            'newest-auto',
            NIPSWeb_AutoPlaylist::findByName('Newest Tracks')->getTracks()
        );
    }


    private static function updateRandomTracksPlaylist()
    {
        if (!self::playlistGenPrepare('random-auto')) {
            return;
        }

        self::playlistGenCommit(
            'random-auto',
            NIPSWeb_AutoPlaylist::findByName('Random Tracks')->getTracks()
        );
    }

    private static function updateLastFMGroupPlaylist()
    {
        if (!self::playlistGenPrepare('lastgroup-auto')) {
            return;
        }

        $data = json_decode(
            file_get_contents(
                'https://ws.audioscrobbler.com/2.0/?method=group.getweeklytrackchart&api_key='
                . Config::$lastfm_api_key
                . '&group=' . Config::$lastfm_group
                . '&format=json'
            ),
            true
        );

        $items = array_map(
            function($m) {
                return [
                    'count' => $m['playcount'],
                    'title' => $m['name'],
                    'artist' => $m['artist']['#text']
                ];
            },
            $data['weeklytrackchart']['track']
        );

        self::playlistGenCommit(
            'lastgroup-auto',
            self::dataSimilarIterator($items, 100, 2)
        );
    }

    private static function updateLastFMGeoPlaylist()
    {
        if (!self::playlistGenPrepare('lastgeo-auto')) {
            return;
        }

        $data = json_decode(
            file_get_contents(
                'https://ws.audioscrobbler.com/2.0/?method=geo.getTopTracks&api_key='
                . Config::$lastfm_api_key
                . '&country=' . Config::$lastfm_geo
                . '&limit=100&format=json'
            ),
            true
        );

        $items = array_map(
            function($m) {
                return [
                    'title' => $m['name'],
                    'artist' => $m['artist']['name']
                ];
            },
            $data['toptracks']['track']
        );

        self::playlistGenCommit(
            'lastgeo-auto',
            self::dataSimilarIterator($items, 0, null, false)
        );

    }

    private static function updateLastFMTopPlaylist()
    {
        if (!self::playlistGenPrepare('lasttop-auto')) {
            return;
        }

        $data = json_decode(
            file_get_contents(
                'https://ws.audioscrobbler.com/2.0/?method=chart.getTopTracks&api_key='
                . Config::$lastfm_api_key
                . '&limit=100&format=json'
            ),
            true
        );

        $items = array_map(
            function($m) {
                return [
                    'title' => $m['name'],
                    'artist' => $m['artist']['name']
                ];
            },
            $data['tracks']['track']
        );

        self::playlistGenCommit(
            'lasttop-auto',
            self::dataSimilarIterator($items, 0, null, false)
        );
    }

    private static function updateLastFMHypePlaylist()
    {
        if (!self::playlistGenPrepare('lasthype-auto')) {
            return;
        }

        $data = json_decode(
            file_get_contents(
                'https://ws.audioscrobbler.com/2.0/?method=chart.getHypedTracks&api_key='
                . Config::$lastfm_api_key
                . '&limit=100&format=json'
            ),
            true
        );

        $items = array_map(
            function($m) {
                return [
                    'title' => $m['name'],
                    'artist' => $m['artist']['name']
                ];
            },
            $data['tracks']['track']
        );

        self::playlistGenCommit(
            'lasthype-auto',
            self::dataSimilarIterator($items, 0, null, false)
        );
    }

}
