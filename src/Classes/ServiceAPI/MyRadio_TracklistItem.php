<?php

/**
 * Provides the TracklistItem class for MyRadio.
 */
namespace MyRadio\ServiceAPI;

use MyRadio\Config;
use MyRadio\MyRadioException;
use MyRadio\MyRadio\CoreUtils;
use MyRadio\MyRadio\AuthUtils;
use MyRadio\iTones\iTones_Playlist;
use MyRadio\iTones\iTones_Utils;

/**
 * The Tracklist Item class provides information about URY's track playing
 * history.
 *
 * @uses    \Database
 */
class MyRadio_TracklistItem extends ServiceAPI
{
    const BASE_TRACKLISTITEM_SQL =
        'SELECT * FROM tracklist.tracklist
            LEFT JOIN tracklist.track_rec USING (audiologid)
            LEFT JOIN tracklist.track_notrec USING (audiologid)';
    private $audiologid;
    private $source;
    private $starttime;
    private $endtime;
    private $state;
    private $timeslot;
    private $bapsaudioid;

    /**
     * MyRadio_Track that was played, or an array of artist, album, track, label, length data.
     */
    private $track;

    protected function __construct($result)
    {
        $this->audiologid = (int) $result['audiologid'];

        $this->source = $result['source'];
        $this->starttime = strtotime($result['timestart']);
        $this->endtime = strtotime($result['timestop']);
        $this->state = $result['state'];
        $this->timeslot = is_numeric($result['timeslotid']) ?
            MyRadio_Timeslot::getInstance($result['timeslotid']) : null;
        $this->bapsaudioid = is_numeric($result['bapsaudioid']) ? (int) $result['bapsaudioid'] : null;

        $this->track = is_numeric($result['trackid']) ? $result['trackid'] :
            [
                'title' => $result['track'],
                'artist' => $result['artist'],
                'album' => $result['album'],
                'trackid' => null,
                'trackno' => (int) $result['trackno'],
                'length' => $result['length'],
                'record_label' => $result['label'],
            ];
    }

    protected static function factory($id)
    {
        $result = self::$db->fetchOne(self::BASE_TRACKLISTITEM_SQL.' WHERE tracklist.audiologid=$1 LIMIT 1', [$id]);
        if (empty($result)) {
            throw new MyRadioException('The requested TracklistItem does not appear to exist.', 404);
        }

        return new self($result);
    }


    /**
     * Create a new TracklistItem, returning the new item.
     *
     * @param int $trackid  The ID of the track to tracklist.
     * @param int $timeslotid   The ID of the timeslot to tracklist to. Optional, defaults to current show.
     * @param int $starttime    Epoch time of the start of the tracklist. Optional, defaults to current time.
     * @param char $sourceid    The id of the tracklist source (baps, webstudio, etc), see tracklist.source. Defaults to 'api'
     * @param char $state       The state of the tracklist, see tracklist.state. Defaults to 'confirmed'
     *
     *
     * @return MyRadio_TracklistItem
     *
     * @throws MyRadioException
     */
    public static function create($trackid, $timeslotid = null, $starttime = null, $sourceid = 'a', $state = 'c')
    {

        if (AuthUtils::hasPermission(AUTH_TRACKLIST_ALL)) {
            $tracklist_all = true;
        }
        else if (AuthUtils::hasPermission(AUTH_TRACKLIST_OWN))
            $tracklist_all = false;
        else {
            throw new MyRadioException("The current user does not have permission to create a tracklistitem.", 403);
        }

        if ($timeslotid != null && $tracklist_all == false) {
            throw new MyRadioException("The current user doesn't have permission to set a tracklist on a show other than their own.", 403);
        }

        if ($timeslotid == null) {
            $timeslot = MyRadio_Timeslot::getCurrentTimeslot();
            $timeslotid = $timeslot != null ? $timeslot->getID() : null; // will be null if jukebox etc.
        } else {
            $timeslot = MyRadio_Timeslot::getInstance($timeslotid);
        }

        if ($timeslot == null) {
            // we're on jukebox
            if ($tracklist_all == false) {
                throw new MyRadioException("The current user doesn't have permission to set a tracklist on a show other than their own.", 403);
            }
        } else {
            if ($tracklist_all == false && !in_array(MyRadio_User::getCurrentUser(), $timeslot->getSeason()->getShow()->getCreditObjects())) {
                throw new MyRadioException("Current user doesn't have permission to tracklist to a show they aren't credited on.", 403);
            }
            if ($timeslot->getStartTime() > $starttime || $timeslot->getEndTime() < $starttime) {
                throw new MyRadioException("The starttime provided was outside the window of the requested timeslot.", 400);
            }

        }


        if ($starttime == null) {
            $starttime = time();
        }

        $track = MyRadio_Track::getInstance($trackid);


        self::$db->query('BEGIN');

        $audiologid = self::$db->fetchOne(
            'INSERT INTO tracklist.tracklist (source, timeslotid, starttime, state)
            VALUES ($1, $2, $3, $4) RETURNING audiologid',
            [$source, $timeslotid, CoreUtils::getTimestamp($time), $state]
        );

        if ($audiologid['audiologid'] == null) {
            self::$db->query('ABORT');
            throw new MyRadioException("Was not able to register tracklist entry. Source is likely invalid.", 400);
        }

        self::$db->query(
            'INSERT INTO tracklist.track_rec (audiologid, recordid, trackid)
            VALUES ($1, $2, $3)',
            [$audiologid['audiologid'], $track->getAlbum()->getID(), $track->getID()]
        );

        self::$db->query('COMMIT');

        return self::getInstance($audiologid['audiologid']);

    }

    public function getEndTime()
    {
        return $this->endtime;
    }

    public function setEndTime()
    {
        if ($this->starttime) {
            $time = CoreUtils::getTimestamp();
            if (AuthUtils::hasPermission(AUTH_TRACKLIST_ALL)
                || (AuthUtils::hasPermission(AUTH_TRACKLIST_OWN)
                    && in_array(MyRadio_User::getCurrentUser(), $this->timeslot->getSeason()->getShow()->getCreditObjects()))
            ) {
                    self::$db->query(
                        'UPDATE tracklist.tracklist SET timestop=$1 WHERE audiologid=$2',
                        [$time, $this->getID()]
                    );
                    $this->endtime = strtotime($time);
                } else {
                    throw new MyRadioException("Current user doesn't have permission to set the endtime of a tracklistitem not from their show.", 403);
                }
        } else {
            throw new MyRadioException("This timeslotitem does not have a start time. An end time therefore cannot be set.", 400);
        }
        return $this;
    }

    public function getID()
    {
        return $this->audiologid;
    }

    public function getTrack()
    {
        return is_array($this->track) ? $this->track :
            MyRadio_Track::getInstance($this->track);
    }

    public function getStartTime()
    {
        return $this->starttime;
    }

    /**
     * Returns an array of all TracklistItems played during the given Timeslot.
     *
     * @param int $timeslotid The ID of the Timeslot
     * @param int $offset     Skip items with an audiologid <= this
     *
     * @return array
     */
    public static function getTracklistForTimeslot($timeslotid, $offset = 0)
    {
        $result = self::$db->fetchAll(
            self::BASE_TRACKLISTITEM_SQL
            .' WHERE timeslotid=$1'
            .' AND (state ISNULL OR state != \'d\')'
            .' AND tracklist.audiologid > $2'
            .' ORDER BY timestart ASC',
            [$timeslotid, $offset]
        );

        $items = [];
        foreach ($result as $item) {
            $items[] = new self($item);
        }

        return $items;
    }

    /**
     * Find all tracks played by Jukebox.
     *
     * @param int  $start           Period to start log from. Default 0.
     * @param int  $end             Period to end log from. Default time().
     * @param bool $include_playout Optional. If true, include statistics from when jukebox was not on air,
     *                              i.e. when it was only feeding campus bars. Default true.
     */
    public static function getTracklistForJukebox($start = null, $end = null, $include_playout = true)
    {
        self::wakeup();

        $start = $start === null ? '1970-01-01 00:00:00' : CoreUtils::getTimestamp($start);
        $end = $end === null ? CoreUtils::getTimestamp() : CoreUtils::getTimestamp($end);

        $result = self::$db->fetchAll(
            self::BASE_TRACKLISTITEM_SQL
            .' WHERE source=\'j\''
            .' AND timestart >= $1 AND timestart <= $2'
            .($include_playout ? '' : ' AND state!=\'u\' AND state!=\'d\''),
            [$start, $end]
        );

        $items = [];
        foreach ($result as $item) {
            $items[] = new self($item);
        }

        return $items;
    }

    /**
     * Find all tracks played in the given timeframe, as datasources.
     * Not datasource runs out of RAM pretty quick.
     *
     * @todo Datasources are a lot nicer than they used to be - revisit this
     *
     * @param int  $start           Period to start log from. Required.
     * @param int  $end             Period to end log from. Default time().
     * @param bool $include_playout If true, includes tracks played on /jukebox or /campus_playout while a show was on.
     */
    public static function getTracklistForTime($start, $end = null, $include_playout = false)
    {
        self::wakeup();

        $start = CoreUtils::getTimestamp($start);
        $end = $end === null ? CoreUtils::getTimestamp() : CoreUtils::getTimestamp($end);

        $result = self::$db->fetchAll(
            self::BASE_TRACKLISTITEM_SQL
            .' WHERE timestart >= $1 AND timestart <= $2 AND (state IS NULL OR state=\'c\''
            .($include_playout ? 'OR state = \'o\')' : ')')
            .' ORDER BY timestart ASC',
            [$start, $end]
        );

        $return = [];
        foreach ($result as $item) {
            if (sizeof($return) == 100000) {
                return $return;
            }

            $obj = new self($item);
            $data = $obj->toDataSource();

            unset($data['audiologid']);
            unset($data['editlink']);
            unset($data['state']);
            unset($data['type']);
            unset($data['length']);
            unset($data['clean']);
            unset($data['digitised']);
            unset($data['deletelink']);
            unset($data['trackno']);
            unset($data['intro']);

            //for manual SIS entries
            if (!isset($data['trackid'])) {
                $data['trackid'] = "SIS Manual";
            }

            if (is_array($data['album'])) {
                $data['label'] = $data['album']['label'];
                $data['album'] = $data['album']['title'];
            } else {
                $data['label'] = $data['record_label'];
                unset($data['record_label']);
            }

            $return[] = $data;
            if (is_object($obj->getTrack())) {
                $obj->getTrack()->removeInstance();
            }
            $obj->removeInstance();
            unset($obj);
        }

        return $return;
    }

    /**
     * Takes as input a result set of num_plays and trackid, and generates the extended Datasource output used by
     * getTracklistStats(.*)().
     *
     * @return Array, 2D, with the inner dimension being a MyRadio_Track Datasource output, with the addition of:
     *                num_plays: The number of times the track was played
     *                total_playtime: The total number of seconds the track has been on air
     *                in_playlists: A CSV of playlists the Track is in
     */
    private static function trackAmalgamator($result, $playlists = true)
    {
        $data = [];
        foreach ($result as $row) {
            /*
             * @todo Temporary hack due to lack of fkey on tracklist.track_rec
             */
            try {
                $trackobj = MyRadio_Track::getInstance($row['trackid']);
            } catch (MyRadioException $e) {
                continue;
            }
            $track = $trackobj->toDataSource();
            $track['num_plays'] = $row['num_plays'];
            $track['total_playtime'] = $row['num_plays'] * $trackobj->getDuration();

            $track['in_playlists'] = '';

            if ($playlists) {
                $playlistobjs = iTones_Playlist::getPlaylistsWithTrack($trackobj);
                $track['in_playlists'] = implode(', ', array_map(function ($i) {
                    return $i->getTitle();
                }, $playlistobjs));
            }

            $data[] = $track;
        }

        return $data;
    }

    /**
     * Get an amalgamation of all tracks played by Jukebox. This looks at all played tracks within the proposed
     * timeframe, and outputs the play count of each Track, including the total time played.
     *
     * @param int  $start           Period to start log from. Default 0.
     * @param int  $end             Period to end log from. Default time().
     * @param bool $include_playout Optional. If true, include statistics from when jukebox was not on air,
     *                              i.e. when it was only feeding campus bars. Default true.
     * @param bool $playlists       Whether to get playlist membership metadata for tracks.
     *
     * @return Array, 2D, with the inner dimension being a MyRadio_Track Datasource output, with the addition of:
     *                num_plays: The number of times the track was played
     *                total_playtime: The total number of seconds the track has been on air
     *                in_playlists: A CSV of playlists the Track is in
     */
    public static function getTracklistStatsForJukebox(
        $start = null,
        $end = null,
        $include_playout = true,
        $playlists = false
    ) {
        self::wakeup();

        $start = $start === null ? '1970-01-01 00:00:00' : CoreUtils::getTimestamp($start);
        $end = $end === null ? CoreUtils::getTimestamp() : CoreUtils::getTimestamp($end);

        $result = self::$db->fetchAll(
            'SELECT COUNT(trackid) AS num_plays, trackid FROM tracklist.tracklist
            LEFT JOIN tracklist.track_rec ON tracklist.audiologid = track_rec.audiologid
            WHERE source=\'j\' AND timestart >= $1 AND timestart <= $2 AND trackid IS NOT NULL'
            .($include_playout ? '' : 'AND state != \'o\'')
            .' GROUP BY trackid ORDER BY num_plays DESC',
            [$start, $end]
        );

        return self::trackAmalgamator($result, $playlists);
    }

    /**
     * Get an amalgamation of all tracks played by BAPS. This looks at all played tracks within the proposed timeframe,
     * and outputs the play count of each Track, including the total time played.
     *
     * @param int  $start     Period to start log from. Default 0.
     * @param int  $end       Period to end log from. Default time().
     * @param bool $playlists Whether to get playlist membership metadata for the tracks.
     *
     * @return Array, 2D, with the inner dimension being a MyRadio_Track Datasource output, with the addition of:
     *                num_plays: The number of times the track was played
     *                total_playtime: The total number of seconds the track has been on air
     *                in_playlists: A CSV of playlists the Track is in
     */
    public static function getTracklistStatsForBAPS($start = null, $end = null, $playlists = false)
    {
        self::wakeup();

        $start = $start === null ? '1970-01-01 00:00:00' : CoreUtils::getTimestamp($start);
        $end = $end === null ? CoreUtils::getTimestamp() : CoreUtils::getTimestamp($end);

        $result = self::$db->fetchAll(
            'SELECT COUNT(trackid) AS num_plays, trackid FROM tracklist.tracklist
            LEFT JOIN tracklist.track_rec ON tracklist.audiologid = track_rec.audiologid
            WHERE source=\'b\' AND timestart >= $1 AND timestart <= $2 AND trackid IS NOT NULL
            GROUP BY trackid ORDER BY num_plays DESC',
            [$start, $end]
        );

        return self::trackAmalgamator($result, $playlists);
    }

    /**
     * Returns if the given track has been played in the last $time seconds.
     *
     * @param MyRadio_Track $track
     * @param int           $time  Optional. Default 21600 (6 hours)
     */
    public static function getIfPlayedRecently(MyRadio_Track $track, $time = 21600)
    {
        $result = self::$db->fetchColumn(
            'SELECT timestart FROM tracklist.tracklist
            LEFT JOIN tracklist.track_rec ON tracklist.audiologid = track_rec.audiologid
            WHERE timestart >= $1 AND trackid = $2',
            [CoreUtils::getTimestamp(time() - $time), $track->getID()]
        );

        return sizeof($result) !== 0;
    }

    /**
     * Check whether queuing the given Track for playout right now would be a
     * breach of our PPL Licence.
     *
     * The PPL Licence states that a maximum of three songs from an album (and no
     * more than two consecutively) AND a maximum of four songs by an artist (and
     * no more than three consecutively) may be broadcast in any two hour period.
     * Any more is a breach of this licence, so we should really stop doing it.
     *
     * @param MyRadio_Track $track
     * @param bool          $include_queue If true, will include the tracks in the iTones queue.
     * @param int           $time          If set, will check if playing it at $time would be a/was a breach.
     *                                     No, this isn't magic and know the future accurately.
     *
     * @return bool
     */
    public static function getIfAlbumArtistCompliant(MyRadio_Track $track, $include_queue = true, $time = null)
    {
        if ($time == null) {
            $time = time();
        }
        $timeout = CoreUtils::getTimestamp($time - 3600); //One hour ago

        /*
         * The title check is a hack to work around our default album
         * being URY Downloads
         */
        $result = self::$db->fetchColumn(
            'SELECT COUNT(*) FROM tracklist.tracklist
            LEFT JOIN tracklist.track_rec USING (audiologid)
            LEFT JOIN (SELECT recordid, title AS album FROM public.rec_record) AS t1
            USING (recordid)
            LEFT JOIN public.rec_track USING (trackid)
            WHERE (rec_track.recordid=$1 OR rec_track.artist=$2)
            AND timestart >= $3
            AND timestart < $4
            AND album NOT ILIKE \''.Config::$short_name.' Downloads%\'',
            [
                $track->getAlbum()->getID(),
                $track->getArtist(),
                $timeout,
                CoreUtils::getTimestamp($time),
            ]
        );

        if ($include_queue) {
            foreach (iTones_Utils::getTracksInAllQueues() as $req) {
                if (empty($req['trackid'])) {
                    continue;
                }
                $t = MyRadio_Track::getInstance($req['trackid']);

                /*
                 * The title check is a hack to work around our default album
                 * being URY Downloads
                 */
                if (($t->getAlbum()->getID() === $track->getAlbum()->getID()
                    && stristr($t->getAlbum()->getTitle(), Config::$short_name.' Downloads') === false)
                    || $t->getArtist() === $track->getArtist()
                ) {
                    ++$result[0];
                }
            }
        }

        return $result[0] == 0;
    }

    public function toDataSource($mixins = [])
    {
        if (is_array($this->track)) {
            // If manually tracklisted, track_norec table is just a plain text album.
            // Make it an array like regular tracks.
            if (!is_array($this->track["album"])) {
                $album = [
                    "title" => $this->track["album"],
                    "recordid" => null,
                    "artist" => $this->track["artist"],
                    "cdid" => null,
                    "date_added" => date('d/m/Y H:i', $this->getStartTime()),
                    "date_released" => null,
                    "format" => "Album",
                    "last_modified" => null,
                    "location" => null,
                    "media" => "Manual Tracklist",
                    "member_add" => null,
                    "member_edit" => null,
                    "record_label" => "",
                    "status" => "digital only",
                    "label" => "Manual Tracklist"
                ];
                $this->track["album"] = $album;
            }
            $return = $this->track;
        } else {
            $return = $this->getTrack()->toDataSource($mixins);
        }
        $return['time'] = $this->getStartTime();
        $return['starttime'] = date('d/m/Y H:i:s', $this->getStartTime());
        $return['endtime'] = $this->getEndTime() == null ? null : date('d/m/Y H:i:s', $this->getEndTime());
        $return['state'] = $this->state;
        $return['audiologid'] = $this->audiologid;

        return $return;
    }
}
