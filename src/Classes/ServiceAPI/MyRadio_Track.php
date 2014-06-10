<?php

/**
 * This file provides the MyRadio_Track class for MyRadio
 * @package MyRadio_Core
 */

/**
 * The MyRadio_Track class provides and stores information about a Track
 *
 * @version 20130609
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @package MyRadio_Core
 * @uses \Database
 * @todo Cache this
 */
class MyRadio_Track extends ServiceAPI
{
    /**
     * The number of the Track on a Record
     * @var int
     */
    private $number;

    /**
     * The title of the Track
     * @var String
     */
    private $title;

    /**
     * The Artist of the Track
     * @var int
     */
    private $artist;

    /**
     * The length of the Track, in seconds
     * @var int
     */
    private $length;

    /**
     * Don't use this.
     * @deprecated
     * @var String
     */
    private $duration;

    /**
     * The genreid of the Track
     * @var char
     */
    private $genre;

    /**
     * How long the intro (non-vocal) part of the track is, in seconds
     * @var int
     */
    private $intro;

    /**
     * Whether the track is clean:<br>
     * y: The track is verified as clean<br>
     * n: The track is verified as unclean<br>
     * u: This track has not been checked for cleanliness
     * @var String
     */
    private $clean;

    /**
     * The Unique ID of this Track
     * @var int
     */
    private $trackid;

    /**
     * The Record this track belongs to
     * @var int
     */
    private $record;

    /**
     * Whether or not there is a digital version of this track stored in the Central Database
     * @var bool
     */
    private $digitised;

    /**
     * The member who digitised this track
     * @var int
     */
    private $digitisedby;

    /**
     * Caches Last.fm's Track.getSimilar response.
     * @var Array
     */
    private $lastfm_similar;

    /**
     * Whether this track is iTones blacklisted
     */
    private $itones_blacklist = null;

    /**
     * Initiates the Track variables
     * @param int $trackid The ID of the track to initialise
     * @todo Genre class
     * @todo Artist normalisation
     */
    protected function __construct($trackid)
    {
        $this->trackid = (int) $trackid;
        $result = self::$db->fetchOne('SELECT * FROM public.rec_track WHERE trackid=$1 LIMIT 1', [$this->trackid]);
        if (empty($result)) {
            throw new MyRadioException('The specified Track does not seem to exist');

            return;
        }

        $this->artist = $result['artist'];
        $this->clean = $result['clean'];
        $this->digitised = ($result['digitised'] == 't') ? true : false;
        $this->digitisedby = empty($result['digitisedby']) ? null : (int) $result['digitisedby'];
        $this->genre = $result['genre'];
        $this->intro = strtotime('1970-01-01 ' . $result['intro'] . '+00');
        $this->length = $result['length'];
        $this->duration = (int) $result['duration'];
        $this->number = (int) $result['intro'];
        $this->record = (int) $result['recordid'];
        $this->title = $result['title'];
    }

    private function updateCachedObject()
    {
        self::$cache->set(self::getCacheKey($this->getID()), $this, Config::$cache_track_timeout);
    }

    /**
     * Returns a "summary" string - the title and artist seperated with a dash.
     * @return String
     */
    public function getSummary()
    {
        return $this->getTitle() . ' - ' . $this->getArtist();
    }

    /**
     * Get the Title of the Track
     * @return String
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Get the Artist of the Track
     * @return String
     */
    public function getArtist()
    {
        return $this->artist;
    }

    /**
     * Get the Album of the Track;
     * @return Album
     */
    public function getAlbum()
    {
        return MyRadio_Album::getInstance($this->record);
    }

    /**
     * Get whether the track is clean
     * @return char
     */
    public function getClean()
    {
        return $this->clean;
    }

    /**
     * Get the unique trackid of the Track
     * @return int
     */
    public function getID()
    {
        return $this->trackid;
    }

    /**
     * Get the length of the Track, in hours:minutes:seconds
     * @return string
     */
    public function getLength()
    {
        return $this->length;
    }

    /**
     * Get the duration of the Track, in seconds
     * @return int
     */
    public function getDuration()
    {
        return $this->duration;
    }

    /**
     * Get whether or not the track is digitised
     * @return bool
     */
    public function getDigitised()
    {
        return $this->digitised;
    }

    public function getDigitisedBy()
    {
        if ($this->digitisedby === null) {
            return null;
        } else {
            return MyRadio_User::getInstance($this->digitisedby);
        }
    }

    /**
     * Update whether or not the track is digitised
     */
    public function setDigitised($digitised)
    {
        $this->digitised = $digitised;
        self::$db->query(
            'UPDATE rec_track SET digitised=$1, digitisedby=$2 WHERE trackid=$3',
            $digitised ? [
                't', $_SESSION['memberid'], $this->getID()
            ] : [
                'f', null, $this->getID()
            ]
        );
        $this->updateCachedObject();
    }

    /**
     * Update whether or not the track is clean
     */
    public function setClean($clean)
    {
        $this->clean = $clean;
        self::$db->query('UPDATE rec_track SET clean=$1 WHERE trackid=$2', [$clean, $this->getID()]);
        $this->updateCachedObject();
    }

    /**
     * Returns an array of key information, useful for Twig rendering and JSON requests
     * @todo Expand the information this returns
     * @return Array
     */
    public function toDataSource()
    {
        return [
            'title' => $this->getTitle(),
            'artist' => $this->getArtist(),
            'type' => 'central', //Tells NIPSWeb Client what this item type is
            'album' => $this->getAlbum()->toDataSource(),
            'trackid' => $this->getID(),
            'length' => $this->getLength(),
            'clean' => $this->clean !== 'n',
            'digitised' => $this->getDigitised(),
            'editlink' => [
                'display' => 'icon',
                'value' => 'script',
                'title' => 'Edit Track',
                'url' => CoreUtils::makeURL('Library', 'editTrack', ['trackid' => $this->getID()])
            ],
            'deletelink' => [
                'display' => 'icon',
                'value' => 'trash',
                'title' => 'Delete (Undigitise) Track',
                'url' => CoreUtils::makeURL('Library', 'deleteTrack', ['trackid' => $this->getID()])
            ]
        ];
    }

    /**
     * Returns an Array of Tracks matching the given partial title
     * @param  String $title     A partial or total title to search for
     * @param  String $artist    a partial or total title to search for
     * @param  int    $limit     The maximum number of tracks to return
     * @param  bool   $digitised Whether the track must be digitised. Default false.
     * @param  bool   $exact     Only return Exact matches (i.e. no %)
     * @return Array  of Track objects
     */
    private static function findByNameArtist($title, $artist, $limit, $digitised = false, $exact = false)
    {
        $result = self::$db->fetchColumn(
            'SELECT trackid
            FROM rec_track, rec_record WHERE rec_track.recordid=rec_record.recordid
            AND rec_track.title '
            . ($exact ? '=$1' : 'ILIKE \'%\' || $1 || \'%\'')
            . 'AND rec_track.artist '
            . ($exact ? '=$2' : 'ILIKE \'%\' || $1 || \'%\'')
            . ($digitised ? ' AND digitised=\'t\'' : '')
            . ' LIMIT $3',
            [$title, $artist, $limit]
        );

        $response = [];
        foreach ($result as $trackid) {
            $response[] = new MyRadio_Track($trackid);
        }

        return $response;
    }

    /**
     *
     * @param Array $options One or more of the following:
     *                       title: String title of the track
     *                       artist: String artist name of the track
     *                       digitised: If true, only return digitised tracks. If false, return any.
     *                       itonesplaylistid: Tracks that are members of the iTones_Playlist id
     *                       limit: Maximum number of items to return. 0 = No Limit
     *                       recordid: int Record id
     *                       lastfmverified: Boolean whether or not verified with Last.fm Fingerprinter. Default any.
     *                       random: If true, sort randomly
     *                       idsort: If true, sort by trackid
     *                       custom: A custom SQL WHERE clause
     *                       precise: If true, will only return exact matches for artist/title
     *                       nocorrectionproposed: If true, will only return items with no correction proposed.
     *                       clean: Default any. 'y' for clean tracks, 'n' for dirty, 'u' for unknown.
     *
     * @todo Limit not accurate for itonesplaylistid queries
     */
    public static function findByOptions($options)
    {
        self::wakeup();

        //Shortcircuit - if itonesplaylistid is the only not-default value, just return the playlist
        $conflict = false;
        foreach (['title', 'artist', 'digitised'] as $k) {
            if (!empty($options[$k])) {
                $conflict = true;
                break;
            }
        }

        if (!$conflict && !empty($options['itonesplaylistid'])) {
            return iTones_Playlist::getInstance($options['itonesplaylistid'])->getTracks();
        }

        if (empty($options['title'])) {
            $options['title'] = '';
        }
        if (empty($options['artist'])) {
            $options['artist'] = $options['title'];
            $firstop = 'OR';
        } else {
            $firstop = 'AND';
        }
        if (empty($options['album'])) {
            $options['album'] = '';
        }
        if (!isset($options['digitised'])) {
            $options['digitised'] = true;
        }
        if (empty($options['itonesplaylistid'])) {
            $options['itonesplaylistid'] = null;
        }
        if (!isset($options['limit'])) {
            $options['limit'] = Config::$ajax_limit_default;
        }
        if (empty($options['recordid'])) {
            $options['recordid'] = null;
        }
        if (empty($options['lastfmverified'])) {
            $options['lastfmverified'] = null;
        }
        if (empty($options['random'])) {
            $options['random'] = null;
        }
        if (empty($options['idsort'])) {
            $options['idsort'] = null;
        }
        if (empty($options['custom'])) {
            $options['custom'] = null;
        }
        if (empty($options['precise'])) {
            $options['precise'] = false;
        }
        if (empty($options['nocorrectionproposed'])) {
            $options['nocorrectionproposed'] = false;
        }
        if (empty($options['clean'])) {
            $options['clean'] = false;
        }

        //Prepare paramaters
        $sql_params = [$options['title'], $options['artist'], $options['album'], $options['precise'] ? '' : '%'];
        $count = 4;
        if ($options['limit'] != 0) {
            $sql_params[] = $options['limit'];
            $count++;
            $limit_param = $count;
        }
        if ($options['clean']) {
            $sql_params[] = $options['clean'];
            $count++;
            $clean_param = $count;
        }

        //Do the bulk of the sorting with SQL
        $result = self::$db->fetchAll(
            'SELECT trackid, rec_track.recordid
            FROM rec_track, rec_record WHERE rec_track.recordid=rec_record.recordid
            AND (rec_track.title ILIKE $4 || $1 || $4'
            . $firstop
            . ' rec_track.artist ILIKE $4 || $2 || $4) AND rec_record.title ILIKE $4 || $3 || $4'
            . ($options['digitised'] ? ' AND digitised=\'t\'' : '')
            . ' '
            . ($options['lastfmverified'] === true ? ' AND lastfm_verified=\'t\'' : '')
            . ($options['lastfmverified'] === false ? ' AND lastfm_verified=\'f\'' : '')
            . ($options['nocorrectionproposed'] === true ? ' AND trackid NOT IN (
            SELECT trackid FROM public.rec_trackcorrection WHERE state=\'p\')' : '')
            . ($options['clean'] != null ? ' AND clean=$' . $clean_param : '')
            . ($options['custom'] !== null ? ' AND ' . $options['custom'] : '')
            . ($options['random'] ? ' ORDER BY RANDOM()' : '')
            . ($options['idsort'] ? ' ORDER BY trackid' : '')
            . ($options['limit'] == 0 ? '' : ' LIMIT $' . $limit_param),
            $sql_params
        );

        $response = [];
        foreach ($result as $trackid) {
            if ($options['recordid'] !== null && $trackid['recordid'] != $options['recordid']) {
                continue;
            }
            $response[] = new MyRadio_Track($trackid['trackid']);
        }

        //Intersect with iTones if necessary, then return
        return empty($options['itonesplaylistid']) ? $response :
            array_intersect(
                $response,
                iTones_Playlist::getInstance($options['itonesplaylistid'])
                ->getTracks()
            );
    }

    /**
     * This method processes an unknown mp3 file that has been uploaded, storing a temporary copy of the file in /tmp/,
     * then attempting to identify the track by querying it against the last.fm database.
     *
     * @param type $tmp_path
     */
    public static function cacheAndIdentifyUploadedTrack($tmp_path)
    {
        if (!isset($_SESSION['myury_nipsweb_file_cache_counter'])) {
            $_SESSION['myury_nipsweb_file_cache_counter'] = 0;
        }
        if (!is_dir(Config::$audio_upload_tmp_dir)) {
            mkdir(Config::$audio_upload_tmp_dir);
        }

        $filename = session_id() . '-' . ++$_SESSION['myury_nipsweb_file_cache_counter'] . '.mp3';

        move_uploaded_file($tmp_path, Config::$audio_upload_tmp_dir . '/' . $filename);

        $getID3 = new getID3;
        $fileInfo = $getID3->analyze(Config::$audio_upload_tmp_dir . '/' . $filename);

        // File quality checks
        if ($fileInfo['audio']['bitrate'] < 192000) {
            return ['status' => 'FAIL', 'error' => 'Bitrate is below 192kbps.', 'fileid' => $filename, 'bitrate' => $fileInfo['audio']['bitrate']];
        }
        if (strpos($fileInfo['audio']['channelmode'], 'stereo') === false) {
            return ['status' => 'FAIL', 'error' => 'Item is not stereo.', 'fileid' => $filename, 'channelmode' => $fileInfo['audio']['channelmode']];
        }

        $analysis = self::identifyUploadedTrack(Config::$audio_upload_tmp_dir . '/' . $filename);
        if (isset($analysis['status']) && ($analysis['status'] === 'FAIL' || $analysis['status'] === 'NO_LASTFM_MATCH')) {
            $analysis['fileid'] = $filename;
            return $analysis;
        } else {
            return [
                'fileid' => $filename,
                'analysis' => $analysis
            ];
        }
    }

    /**
     * Attempts to identify an MP3 file against the last.fm database.
     *
     * !This method requires the external lastfm-fpclient application to be installed on the server. A FreeBSD build
     * with URY's API key and support for -json can be found in the fpclient.git URY Git repository.
     *
     * @param  String $path The location of the MP3 file
     * @return Array  A parsed array version of the JSON lastfm response
     */
    public static function identifyUploadedTrack($path)
    {
        //Syspath is set by Daemons or where $PATH is not sufficent.
        $response = shell_exec((empty($GLOBALS['syspath']) ? '' : $GLOBALS['syspath']) . 'lastfm-fpclient -json ' . $path);
        //echo (empty($GLOBALS['syspath']) ? '' : $GLOBALS['syspath']).'lastfm-fpclient -json ' . $path;

        $lastfm = json_decode($response, true);
        $lastfm = [];

        if (empty($lastfm)) {
            if (CoreUtils::hasPermission(AUTH_UPLOADMUSICMANUAL)) {
                return [
                    'status' => 'NO_LASTFM_MATCH',
                    'error' => 'Track not found in Last FM.'
                ];
            }
            else {
                return [
                    'status' => 'FAIL',
                    'error' => 'This track could not be identified. Please email the track to track.requests@ury.org.uk.'
                ];
            }
        } else {
            if (isset($lastfm['tracks']['track']['mbid'])) {
                //Only one match
                return [
                    ['title' => $lastfm['tracks']['track']['name'],
                        'artist' => $lastfm['tracks']['track']['artist']['name'],
                        'rank' => $lastfm['tracks']['track']['@attr']['rank']]
                ];
            }

            $tracks = [];
            if (empty($lastfm['tracks']['track'])) {
                return [];
            }

            foreach ($lastfm['tracks']['track'] as $track) {
                $tracks[] = ['title' => $track['name'], 'artist' => $track['artist']['name'], 'rank' => $track['@attr']['rank']];
            }

            return $tracks;
        }
    }

    public static function identifyAndStoreTrack($tmpid, $title, $artist, $album, $position)
    {
        $ainfo = null;
        if ($album === "FROM_LASTFM") {
            // Get the album info if we're getting it from lastfm
            $ainfo = self::getAlbumDurationAndPositionFromLastfm($title, $artist);
        } else {
            // Use the album title the user has provided. Use an existing album
            // if we already have one of that title. If not, create one.
            $myradio_album = MyRadio_Album::findOrCreate($album, $artist);
            $ainfo = array('duration' => null, 'position' => intval($position), 'album' => $myradio_album);
        }

        // Get the track duration from the file if it isn't already set
        if (empty($ainfo['duration'])) {
            $getID3 = new getID3;
            $ainfo['duration'] = intval($getID3->analyze(Config::$audio_upload_tmp_dir . '/' . $tmpid)['playtime_seconds']);
        }

        // Check if the track is already in the library and create it if not
        $track = self::findByNameArtist($title, $artist, 1, false, true);
        if (empty($track)) {
            //Create the track
            $track = self::create([
                        'title' => $title,
                        'artist' => $artist,
                        'digitised' => true,
                        'duration' => $ainfo['duration'],
                        'recordid' => $ainfo['album']->getID(),
                        'number' => $ainfo['position']
            ]);
        } else {
            $track = $track[0];
            //If it's set to digitised, throw an error
            if ($track->getDigitised()) {
                return ['status' => 'FAIL', 'error' => 'This track is already in our library.'];
            } else {
                //Mark it as digitised
                $track->setDigitised(true);
            }
        }

        /**
         * Store three versions of the track:
         * 1- 192kbps MP3 for BAPS and Chrome/IE
         * 2- 192kbps OGG for Safari/Firefox
         * 3- Original file for potential future conversions
         */
        $tmpfile = Config::$audio_upload_tmp_dir . '/' . $tmpid;
        $dbfile = $ainfo['album']->getFolder() . '/' . $track->getID();

        shell_exec("nice -n 15 ffmpeg -i '$tmpfile' -ab 192k -f mp3 - >'{$dbfile}.mp3'");
        shell_exec("nice -n 15 ffmpeg -i '$tmpfile' -acodec libvorbis -ab 192k '{$dbfile}.ogg'");
        rename($tmpfile, $dbfile . '.mp3.orig');

        return ['status' => 'OK'];
    }

    /**
     * Create a new MyRadio_Track with the provided options
     * @param  Array            $options
     *                                   title (required): Title of the track.
     *                                   artist (required): (string) Artist of the track.
     *                                   recordid (required): (int) Album of track.
     *                                   duration (required): Duration of the track, in seconds
     *                                   number: Position of track on album
     *                                   genre: Character code genre of track
     *                                   intro: Length of track intro, in seconds
     *                                   clean: 'y' yes, 'n' no, 'u' unknown lyric cleanliness status
     *                                   digitised: boolean digitised status
     * @return MyRadio_Track    a shiny new MyRadio_Track with the provided options
     * @throws MyRadioException
     */
    public static function create($options)
    {
        self::wakeup();

        $required = ['title', 'artist', 'recordid', 'duration'];
        foreach ($required as $require) {
            if (empty($options[$require])) {
                throw new MyRadioException($require . ' is required to create a Track.', 400);
            }
        }

//Number 0
        if (empty($options['number'])) {
            $options['number'] = 0;
        }
//Other Genre
        if (empty($options['genre'])) {
            $options['genre'] = 'o';
        }
//No intro
        if (empty($options['intro'])) {
            $options['intro'] = 0;
        }
//Clean unknown
        if (empty($options['clean'])) {
            $options['clean'] = 'u';
        }
//Not digitised, and formate to t/f
        if (empty($options['digitised'])) {
            $options['digitised'] = 'f';
        } else {
            $options['digitised'] = $options['digitised'] ? 't' : 'f';
        }

        $result = self::$db->query(
            'INSERT INTO rec_track (number, title, artist, length, genre, intro, clean, recordid, digitised, digitisedby, duration)
            VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11) RETURNING trackid',
            [
                $options['number'],
                trim($options['title']),
                trim($options['artist']),
                CoreUtils::intToTime($options['duration']),
                $options['genre'],
                CoreUtils::intToTime($options['intro']),
                $options['clean'],
                $options['recordid'],
                $options['digitised'],
                $_SESSION['memberid'],
                $options['duration']
            ]
        );

        $id = self::$db->fetchAll($result);

        return self::getInstance($id[0]['trackid']);
    }

    public function updateInfoFromLastfm()
    {
        $details = self::getAlbumDurationAndPositionFromLastfm($this->title, $this->artist);

        $this->setAlbum($details['album']);
        $this->setPosition($details['position']);
        $this->setDuration($details['duration']);
    }

    public function setAlbum(MyRadio_Album $album)
    {
        if ($album->getID() === $this->getAlbum()->getID()) {
            return;
        }
        //Move the file
        foreach (Config::$music_central_db_exts as $ext) {
            if (!file_exists($this->getPath($ext))) {
                continue;
            }
            $new_dir = Config::$music_central_db_path . '/records/' . $album->getID();
            if (!is_dir($new_dir)) {
                mkdir($new_dir);
            }
            $new_path = $new_dir . '/' . $this->getID() . '.' . $ext;
            if (!copy($this->getPath($ext), $new_path)) {
                throw new MyRadioException('Failed to move file from ' . $this->getPath($ext) . ' to ' . $new_path);
            }
            unlink($this->getPath($ext));
        }

        $this->record = $album->getID();
        self::$db->query('UPDATE rec_track SET recordid=$1 WHERE trackid=$2', [$album->getID(), $this->getID()]);

        $this->updateCachedObject();
    }

    public function setTitle($title)
    {
        if (empty($title)) {
            throw new MyRadioException('Track title must not be empty!');
        }

        $this->title = $title;
        self::$db->query('UPDATE rec_track SET title=$1 WHERE trackid=$2', [$title, $this->getID()]);
        $this->updateCachedObject();
    }

    public function setArtist($artist)
    {
        if (empty($artist)) {
            throw new MyRadioException('Track artist must not be empty!');
        }

        $this->artist = $artist;
        self::$db->query('UPDATE rec_track SET artist=$1 WHERE trackid=$2', [$artist, $this->getID()]);

        $this->updateCachedObject();
    }

    public function setPosition($position)
    {
        $this->position = (int) $position;
        self::$db->query('UPDATE rec_track SET number=$1 WHERE trackid=$2', [$this->getPosition(), $this->getID()]);
        $this->updateCachedObject();
    }

    public function getPosition()
    {
        return $this->position;
    }

    public function setDuration($duration)
    {
        $this->duration = (int) $duration;
        self::$db->query('UPDATE rec_track SET length=$1, duration=$2 WHERE trackid=$3', [
            CoreUtils::intToTime($this->getDuration()),
            $this->getDuration(),
            $this->getID()
        ]);
        $this->updateCachedObject();
    }

    /**
     * Returns all Tracks that are marked as digitsed in the library
     *
     * @return MyRadio_Track[] An array of digitised Tracks
     */
    public static function getAllDigitised()
    {
        self::initDB();
        $ids = self::$db->fetchColumn('SELECT trackid FROM rec_track WHERE digitised=\'t\'');

        $tracks = [];
        foreach ($ids as $id) {
            $tracks[] = self::getInstance($id);
        }

        return $tracks;
    }

    /**
     * Returns the physical path to the Track
     * @param  String $format Optional file extension - at time of writing this could me "mp3", "ogg" or "mp3.orig"
     * @return String path to Track file
     */
    public function getPath($format = 'mp3')
    {
        return Config::$music_central_db_path . '/records/' . $this->getAlbum()->getID() . '/' . $this->getID() . '.' . $format;
    }

    /**
     * Returns whether this track's physical file exists
     * @param  String $format Optional file extension - at time of writing this could me "mp3", "ogg" or "mp3.orig"
     * @return bool   If the file exists
     */
    public function checkForAudioFile($format = 'mp3')
    {
        return file_exists($this->getPath($format));
    }

    /**
     * Queries the last.fm API to find information about a track with the given title/artist combination
     * @param  String $title  track title
     * @param  String $artist track artist
     * @return array  album: MyRadio_Album object matching the input
     *                       position: The track number on the album
     *                       duration: The length of the track, in seconds
     */
    public static function getAlbumDurationAndPositionFromLastfm($title, $artist)
    {
        $details = json_decode(
            file_get_contents(
                'https://ws.audioscrobbler.com/2.0/?method=track.getInfo&api_key='
                . Config::$lastfm_api_key
                . '&artist=' . urlencode($artist)
                . '&track=' . urlencode(str_replace(' (Radio Edit)', '', $title))
                . '&format=json'
            ),
            true
        );

        if (!isset($details['track']['album'])) {
            //Send some defaults for album info
            return [
                'album' => MyRadio_Album::findOrCreate(Config::$short_name . ' Downloads ' . date('Y'), Config::$short_name),
                'position' => 0,
                'duration' => intval($details['track']['duration'] / 1000)
            ];
        }

        return [
            'album' => MyRadio_Album::findOrCreate($details['track']['album']['title'], $details['track']['album']['artist']),
            'position' => (int) $details['track']['album']['@attr']['position'],
            'duration' => intval($details['track']['duration'] / 1000)
        ];
    }

    public function setLastfmVerified()
    {
        self::$db->query('UPDATE rec_track SET lastfm_verified=\'t\' WHERE trackid=$1', [$this->getID()]);
    }

    /**
     * Get similar Tracks from last.fm. Caches on first call.
     *
     * The number of results will vary - the Last.fm API is asked for 50 matches,
     * of which only ones with a score of 0.25 or higher will be checked,
     * and then only tracks that are in URY's music library returned.
     *
     * @todo Last.fm API Rate limit checks
     * @return MyRadio_Track[]
     */
    public function getSimilar()
    {
        if (empty($this->lastfm_similar)) {
            $data = json_decode(
                file_get_contents(
                    'https://ws.audioscrobbler.com/2.0/?method=track.getSimilar&api_key='
                    . Config::$lastfm_api_key
                    . '&track=' . urlencode($this->getTitle())
                    . '&artist=' . urlencode($this->getArtist())
                    . '&limit=50&format=json'
                ),
                true
            );

            if (!is_array($data['similartracks']['track'])) {
                trigger_error($this . ' had an empty Similar Tracks result.');

                return [];
            }
            foreach ($data['similartracks']['track'] as $r) {
                if ($r['match'] >= 0.25) {
                    $c = self::findByOptions(
                        [
                            'title' => $r['name'],
                            'artist' => $r['artist']['name'],
                            'limit' => 1,
                            'digitised' => true
                        ]
                    );
                    if (!empty($c)) {
                        $this->lastfm_similar[] = $c[0]->getID();
                    }
                }
            }

            $this->updateCachedObject();
        }

        return self::resultSetToObjArray($this->lastfm_similar);
    }

    /**
     * Returns whether the Track is iTones Blacklisted
     * @return bool
     */
    public function isBlacklisted()
    {
        if ($this->itones_blacklist === null) {
            $this->itones_blacklist = (bool) self::$db->numRows(
                self::$db->query(
                    'SELECT * FROM jukebox.track_blacklist
                    WHERE trackid=$1',
                    [$this->getID()]
                )
            );
            $this->updateCachedObject();
        }

        return $this->itones_blacklist;
    }

    /**
     * Returns various numbers that look pretty on a graph, which concern the Central Music Library.
     *
     * The format is compatible with Google Charts.
     *
     * @return Array
     */
    public static function getLibraryStats()
    {
        $num_digitised = (int) self::$db->fetchColumn('SELECT COUNT(*) FROM public.rec_track WHERE digitised=\'t\'')[0];
        $num_undigitised = (int) self::$db->fetchColumn('SELECT COUNT(*) FROM public.rec_track WHERE digitised=\'f\'')[0];
        $num_clean = (int) self::$db->fetchColumn('SELECT COUNT(*) FROM public.rec_track WHERE clean=\'y\'')[0];
        $num_unclean = (int) self::$db->fetchColumn('SELECT COUNT(*) FROM public.rec_track WHERE clean=\'n\'')[0];
        $num_cleanunknown = (int) self::$db->fetchColumn('SELECT COUNT(*) FROM public.rec_track WHERE clean=\'u\'')[0];
        $num_verified = (int) self::$db->fetchColumn('SELECT COUNT(*) FROM public.rec_track WHERE digitised=\'t\' AND lastfm_verified=\'t\'')[0];
        $num_unverified = (int) self::$db->fetchColumn('SELECT COUNT(*) FROM public.rec_track WHERE digitised=\'t\' AND lastfm_verified=\'f\'')[0];

        $num_singles = (int) self::$db->fetchColumn('SELECT COUNT(*) FROM public.rec_record WHERE format=\'s\'')[0];
        $num_albums = (int) self::$db->fetchColumn('SELECT COUNT(*) FROM public.rec_record WHERE format=\'a\'')[0];

        return [
            ['Key', 'Value'],
            ['Digitised', $num_digitised],
            ['Undigitised', $num_undigitised],
            ['Clean Lyrics', $num_clean],
            ['Unclean Lyrics', $num_unclean],
            ['Unverified Lyrics', $num_cleanunknown],
            ['Singles', $num_singles],
            ['Albums', $num_albums],
            ['Verified Metadata', $num_verified],
            ['Unverified Metadata', $num_unverified]
        ];
    }
}
