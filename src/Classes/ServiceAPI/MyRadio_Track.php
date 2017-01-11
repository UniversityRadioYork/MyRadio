<?php

/**
 * This file provides the MyRadio_Track class for MyRadio.
 */
namespace MyRadio\ServiceAPI;

use MyRadio\Config;
use MyRadio\MyRadioException;
use MyRadio\MyRadio\CoreUtils;
use MyRadio\MyRadio\URLUtils;
use MyRadio\MyRadio\MyRadioForm;
use MyRadio\MyRadio\MyRadioFormField;
use MyRadio\iTones\iTones_Playlist;

/**
 * The MyRadio_Track class provides and stores information about a Track.
 *
 * @uses    \Database
 */
class MyRadio_Track extends ServiceAPI
{
    const BASE_TRACK_SQL = 'SELECT * FROM public.rec_track';

    /**
     * The number of the Track on a Record.
     *
     * @var int
     */
    private $number;

    /**
     * The title of the Track.
     *
     * @var string
     */
    private $title;

    /**
     * The Artist of the Track.
     *
     * @var int
     */
    private $artist;

    /**
     * The length of the Track, in seconds.
     *
     * @var int
     */
    private $length;

    /**
     * Don't use this.
     *
     * @deprecated
     *
     * @var string
     */
    private $duration;

    /**
     * The genreid of the Track.
     *
     * @var char
     */
    private $genre;

    /**
     * How long the intro (non-vocal) part of the track is, in seconds.
     *
     * @var int
     */
    private $intro;

    /**
     * Whether the track is clean:<br>
     * y: The track is verified as clean<br>
     * n: The track is verified as unclean<br>
     * u: This track has not been checked for cleanliness.
     *
     * @var string
     */
    private $clean;

    /**
     * The Unique ID of this Track.
     *
     * @var int
     */
    private $trackid;

    /**
     * The Record this track belongs to.
     *
     * @var int
     */
    private $record;

    /**
     * Whether or not there is a digital version of this track stored in the Central Database.
     *
     * @var bool
     */
    private $digitised;

    /**
     * The member who digitised this track.
     *
     * @var int
     */
    private $digitisedby;

    /**
     * Caches Last.fm's Track.getSimilar response.
     *
     * @var array
     */
    private $lastfm_similar;

    /**
     * Whether this track is iTones blacklisted.
     */
    private $itones_blacklist = null;

    /**
     * Initiates the Track variables.
     *
     * @param array $result
     *                      artist string
     *                      clean char y/n/u
     *                      digitised bool
     *                      digitisedby int
     *                      genre int
     *                      intro string HH:ii:ss
     *                      length string HH:ii:ss
     *                      duration int
     *                      intro int
     *                      recordid int
     *                      title string
     *
     * @todo Genre class
     * @todo Artist normalisation
     */
    protected function __construct($result)
    {
        $this->trackid = (int) $result['trackid'];
        $this->artist = $result['artist'];
        $this->clean = $result['clean'];
        $this->digitised = ($result['digitised'] == 't') ? true : false;
        $this->digitisedby = empty($result['digitisedby']) ? null : (int) $result['digitisedby'];
        $this->genre = $result['genre'];
        $this->intro = strtotime('1970-01-01 '.$result['intro'].'+00');
        $this->length = $result['length'];
        $this->duration = (int) $result['duration'];
        $this->number = (int) $result['intro'];
        $this->record = (int) $result['recordid'];
        $this->title = $result['title'];
    }

    /**
     * @throws MyRadioException if the track does not exist
     *
     * @return MyRadio_Track
     */
    protected static function factory($trackid)
    {
        $sql = self::BASE_TRACK_SQL.' WHERE trackid=$1 LIMIT 1';
        $result = self::$db->fetchOne($sql, [$trackid]);

        if (empty($result)) {
            throw new MyRadioException('The specified Track does not seem to exist', 404);
        }

        return new self($result);
    }

    public static function getForm()
    {
        return (
            new MyRadioForm(
                'lib_edittrack',
                'Library',
                'editTrack',
                [
                    'title' => 'Edit Track',
                ]
            )
        )->addField(
            new MyRadioFormField('title', MyRadioFormField::TYPE_TEXT, ['label' => 'Title'])
        )->addField(
            new MyRadioFormField('artist', MyRadioFormField::TYPE_TEXT, ['label' => 'Artist'])
        )->addField(
            new MyRadioFormField('album', MyRadioFormField::TYPE_ALBUM, ['label' => 'Album'])
        );
    }

    public function getEditForm()
    {
        return self::getForm()
            ->editMode(
                $this->getID(),
                [
                    'title' => $this->getTitle(),
                    'artist' => $this->getArtist(),
                    'album' => $this->getAlbum()->getID(),
                ]
            );
    }

    /**
     * Returns a "summary" string - the title and artist seperated with a dash.
     *
     * @return string
     */
    public function getSummary()
    {
        return $this->getTitle().' - '.$this->getArtist();
    }

    /**
     * Get the Title of the Track.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Get the Artist of the Track.
     *
     * @return string
     */
    public function getArtist()
    {
        return $this->artist;
    }

    /**
     * Get the Album of the Track.
     *
     * @return Album
     */
    public function getAlbum()
    {
        return MyRadio_Album::getInstance($this->record);
    }

    /**
     * Get the intro duration of the Track, in seconds.
     *
     * @return int
     */
    public function getIntro()
    {
        return $this->intro;
    }

    /**
     * Get whether the track is clean.
     *
     * @return char
     */
    public function getClean()
    {
        return $this->clean;
    }

    /**
     * Get the unique trackid of the Track.
     *
     * @return int
     */
    public function getID()
    {
        return $this->trackid;
    }

    /**
     * Get the length of the Track, in hours:minutes:seconds.
     *
     * @return string
     */
    public function getLength()
    {
        return $this->length;
    }

    /**
     * Get the duration of the Track, in seconds.
     *
     * @return int
     */
    public function getDuration()
    {
        return $this->duration;
    }

    /**
     * Get whether or not the track is digitised.
     *
     * @return bool
     */
    public function getDigitised()
    {
        return $this->digitised;
    }

    public function getDigitisedBy()
    {
        if ($this->digitisedby === null) {
            return;
        } else {
            return MyRadio_User::getInstance($this->digitisedby);
        }
    }

    /**
     * Update whether or not the track is digitised.
     *
     * @param bool $digitised
     */
    public function setDigitised($digitised)
    {
        $this->digitised = $digitised;
        self::$db->query(
            'UPDATE rec_track SET digitised=$1, digitisedby=$2 WHERE trackid=$3',
            $digitised ? [
                't', $_SESSION['memberid'], $this->getID(),
            ] : [
                'f', null, $this->getID(),
            ]
        );
        $this->updateCacheObject();
    }

    /**
     * Update whether or not the track is clean.
     */
    public function setClean($clean)
    {
        $this->clean = $clean;
        self::$db->query('UPDATE rec_track SET clean=$1 WHERE trackid=$2', [$clean, $this->getID()]);
        $this->updateCacheObject();
    }

    /**
     * Returns an array of key information, useful for Twig rendering and JSON requests.
     *
     * @todo Expand the information this returns
     *
     * @return array
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
            'intro' => $this->getIntro(),
            'clean' => $this->clean !== 'n',
            'digitised' => $this->getDigitised(),
            'editlink' => [
                'display' => 'icon',
                'value' => 'pencil',
                'title' => 'Edit Track',
                'url' => URLUtils::makeURL('Library', 'editTrack', ['trackid' => $this->getID()]),
            ],
            'deletelink' => [
                'display' => 'icon',
                'value' => 'trash',
                'title' => 'Delete (Undigitise) Track',
                'url' => URLUtils::makeURL('Library', 'deleteTrack', ['trackid' => $this->getID()]),
            ],
        ];
    }

    /**
     * Returns an Array of Tracks matching the given partial title.
     *
     * @param string $title     A partial or total title to search for
     * @param string $artist    a partial or total title to search for
     * @param int    $limit     The maximum number of tracks to return
     * @param bool   $digitised Whether the track must be digitised. Default false.
     * @param bool   $exact     Only return Exact matches (i.e. no %)
     *
     * @return array of Track objects
     */
    private static function findByNameArtist($title, $artist, $limit, $digitised = false, $exact = false)
    {
        if ($exact) {
            $result = self::$db->fetchColumn(
                'SELECT trackid
                FROM rec_track
                WHERE title=$1 AND artist=$2'
                .($digitised ? ' AND digitised=\'t\'' : '')
                .' LIMIT $3',
                [$title, $artist, $limit]
            );
        } else {
            $opts = [$title, $limit];
            if ($artist) {
                $opts[] = $artist;
            }
            $result = self::$db->fetchColumn(
                'SELECT trackid FROM (
                    SELECT DISTINCT trackid, priority FROM
                    (
                        (
                            SELECT trackid, 1 AS priority
                            FROM rec_track WHERE title ILIKE $1'
                            .($artist ? ' AND artist=$3' : '')
                            .($digitised ? ' AND digitised=\'t\'' : '').'
                        ) UNION (
                            SELECT trackid, 2 AS priority
                            FROM rec_track WHERE title ILIKE $1 || \'%\''
                            .($artist ? '  AND artist ILIKE $3 || \'%\'' : '')
                            .($digitised ? ' AND digitised=\'t\'' : '').'
                        ) UNION (
                            SELECT trackid, 3 AS priority
                            FROM rec_track WHERE title ILIKE \'%\' || $1 || \'%\''
                            .($artist ? ' AND artist ILIKE \'%\' || $3 || \'%\'' : '')
                            .($digitised ? ' AND digitised=\'t\'' : '').'
                        )
                    ) AS t1
                ) As t2
                ORDER BY priority LIMIT $2',
                $opts
            );
        }

        return self::resultSetToObjArray(array_unique($result));
    }

    /**
     * @param array $options One or more of the following:
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
     *                       precise: If true, will only return exact matches for artist/title(/album if specified)
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

        //Shortcircuit - there's a far simpler and more accurate method
        // if there's only title/artist/digitised/limit
        if (!$options['itonesplaylistid']
            && !$options['recordid']
            && !$options['lastfmverified']
            && !$options['random']
            && !$options['idsort']
            && !$options['custom']
            && !$options['nocorrectionproposed']
            && !$options['clean']
        ) {
            return self::findByNameArtist(
                $options['title'],
                $firstop === 'OR' ? null : $options['artist'],
                $options['limit'],
                $options['digitised'],
                $options['precise']
            );
        }

        //Prepare paramaters
        $sql_params = [$options['precise'] ? '' : '%', $options['title'], $options['artist']];
        $count = 3;
        if ($options['album']) {
            $sql_params[] = $options['album'];
            ++$count;
            $album_param = $count;
        }
        if ($options['limit'] != 0) {
            $sql_params[] = $options['limit'];
            ++$count;
            $limit_param = $count;
        }
        if ($options['clean']) {
            $sql_params[] = $options['clean'];
            ++$count;
            $clean_param = $count;
        }

        //Do the bulk of the sorting with SQL
        $result = self::$db->fetchAll(
            'SELECT trackid, rec_track.recordid
            FROM rec_track, rec_record WHERE rec_track.recordid=rec_record.recordid
            AND (rec_track.title ILIKE $1 || $2 || $1'
            .$firstop
            .' rec_track.artist ILIKE $1 || $3 || $1)'
            .($options['album'] ? ' AND rec_record.title ILIKE $1 || $'.$album_param.' || $1' : '')
            .($options['digitised'] ? ' AND digitised=\'t\'' : '')
            .' '
            .($options['lastfmverified'] === true ? ' AND lastfm_verified=\'t\'' : '')
            .($options['lastfmverified'] === false ? ' AND lastfm_verified=\'f\'' : '')
            .($options['nocorrectionproposed'] === true ? ' AND trackid NOT IN (
            SELECT trackid FROM public.rec_trackcorrection WHERE state=\'p\')' : '')
            .($options['clean'] != null ? ' AND clean=$'.$clean_param : '')
            .($options['custom'] !== null ? ' AND '.$options['custom'] : '')
            .($options['random'] ? ' ORDER BY RANDOM()' : '')
            .($options['idsort'] ? ' ORDER BY trackid' : '')
            .($options['limit'] == 0 ? '' : ' LIMIT $'.$limit_param),
            $sql_params
        );

        $response = [];
        foreach ($result as $trackid) {
            if ($options['recordid'] !== null && $trackid['recordid'] != $options['recordid']) {
                continue;
            }
            $response[] = new self($trackid['trackid']);
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

        $filename = session_id().'-'.++$_SESSION['myury_nipsweb_file_cache_counter'].'.mp3';

        if (!move_uploaded_file($tmp_path, Config::$audio_upload_tmp_dir.'/'.$filename)) {
            throw new MyRadioException('Failed to move uploaded track to tmp directory.', 500);
        }

        $getID3 = new \getID3();
        $fileInfo = $getID3->analyze(Config::$audio_upload_tmp_dir.'/'.$filename);
        $getID3_lib = new \getID3_lib();
        $getID3_lib->CopyTagsToComments($fileInfo);
        

        // File quality checks
        if ($fileInfo['audio']['bitrate'] < 192000) {
            return ['status' => 'FAIL', 'message' => 'Bitrate is below 192kbps', 'fileid' => $filename, 'bitrate' => $fileInfo['audio']['bitrate']];
        } else if (strpos($fileInfo['audio']['channelmode'], 'stereo') === false) {
            return ['status' => 'FAIL', 'message' => 'Item is not stereo', 'fileid' => $filename, 'channelmode' => $fileInfo['audio']['channelmode']];
        } else {
            $analysis['status'] = 'INFO';
            $analysis['message'] = 'Currently editing track information for';
            $analysis['submittable'] = True;
            $analysis['fileid'] = $filename;
            $analysis['analysis']['title'] = $fileInfo['comments_html']['title'];
            $analysis['analysis']['artist'] = $fileInfo['comments_html']['artist'];
            $analysis['analysis']['album'] = $fileInfo['comments_html']['album'];
            $analysis['analysis']['position'] = $fileInfo['comments_html']['track_number'];

            $trackName = implode("", $fileInfo['comments_html']['title']);
            if (stripos($trackName, 'explicit') == true) {
                $analysis['analysis']['explicit'] = true;
            } else {
                $analysis['analysis']['explicit'] = false;
            }
            return $analysis;
        }
    }

    /**
     * Attempts to identify an MP3 file against the last.fm database.
     *
     * !This method requires the external lastfm-fpclient application to be installed on the server. A FreeBSD build
     * with URY's API key and support for -json can be found in the fpclient.git URY Git repository.
     *
     * @param string $path The location of the MP3 file
     *
     * @return array A parsed array version of the JSON lastfm response
     */
    public static function identifyUploadedTrack($path)
    {
        //Syspath is set by Daemons or where $PATH is not sufficent.
        $response = shell_exec((empty($GLOBALS['syspath']) ? '' : $GLOBALS['syspath']).'lastfm-fpclient -json '.$path);

        if (!trim($response)) {
            return ['status' => 'LASTFM_ERROR',
                    'error' => 'Last.FM doesn\'t seem to be working right now.', ];
        }

        $lastfm = json_decode($response, true);

        if (empty($lastfm)) {
            return ['status' => 'NO_LASTFM_MATCH',
                    'error' => 'Track not found in Last FM.', ];
        } else {
            if (isset($lastfm['tracks']['track']['mbid'])) {
                //Only one match
                return [
                    ['title' => $lastfm['tracks']['track']['name'],
                        'artist' => $lastfm['tracks']['track']['artist']['name'],
                        'rank' => $lastfm['tracks']['track']['@attr']['rank'], ],
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

    public static function identifyAndStoreTrack($tmpid, $title, $artist, $album, $position, $explicit = null)
    {
        // We need to rollback if something goes wrong later
        self::$db->query('BEGIN');

        $track = self::findByNameArtist($title, $artist, 1, false, true);

        $ainfo = null;
        if ($album == 'FROM_LASTFM') {
            // Get the album info if we're getting it from lastfm
            $ainfo = self::getAlbumDurationAndPositionFromLastfm($title, $artist);
        } else {
            if (!empty($track)) {
                $myradio_album = $track[0]->getAlbum();
            } else {
                // Use the album title the user has provided. Use an existing album
                // if we already have one of that title. If not, create one.
                $myradio_album = MyRadio_Album::findOrCreate($album, $artist);
            }
            $ainfo = array('duration' => null, 'position' => intval($position), 'album' => $myradio_album);
        }

        // Get the track duration from the file if it isn't already set
        if (empty($ainfo['duration'])) {
            $getID3 = new \getID3();
            $ainfo['duration'] = intval($getID3->analyze(Config::$audio_upload_tmp_dir.'/'.$tmpid)['playtime_seconds']);
        }

        // See if the explicit is set, and set the value for the DB accordingly - if not set unknown
        if (!is_null($explicit)) {
            if ($explicit === true) {
                $clean = 'n';
            } elseif ($explicit === false) {
                $clean = 'y';
            }
        } else {
            $clean = 'u';
        }

        // Check if the track is already in the library and create it if not
        if (empty($track)) {
            //Create the track
            $track = self::create(
                [
                        'title' => $title,
                        'artist' => $artist,
                        'digitised' => true,
                        'duration' => $ainfo['duration'],
                        'recordid' => $ainfo['album']->getID(),
                        'number' => $ainfo['position'],
                        'clean' => $clean,
                ]
            );
        } else {
            $track = $track[0];
            //If it's set to digitised, throw an error
            if ($track->getDigitised()) {
                return ['status' => 'FAIL', 'error' => 'This track is already in our library.'];
            } else {
                //Mark it as digitised/explicit
                $track->setDigitised(true);
                $track->setClean($clean);
            }
        }

        /*
         * Store three versions of the track:
         * 1- 192kbps MP3 for BAPS and Chrome/IE
         * 2- 192kbps OGG for Safari/Firefox
         * 3- Original file for potential future conversions
         */
        $tmpfile = Config::$audio_upload_tmp_dir.'/'.$tmpid;
        $dbfile = $ainfo['album']->getFolder().'/'.$track->getID();

        if (`which ffmpeg`) {
            $bin = 'ffmpeg';
        } elseif (`which avconv`) {
            $bin = 'avconv';
        } else {
            throw new MyRadioException('Could not find ffmpeg or avconv.', 500);
        }

        shell_exec("nice -n 15 $bin -i '$tmpfile' -ab 192k -f mp3 -map 0:a '{$dbfile}.mp3'");
        shell_exec("nice -n 15 $bin -i '$tmpfile' -acodec libvorbis -ab 192k -map 0:a '{$dbfile}.ogg'");
        rename($tmpfile, $dbfile.'.mp3.orig');

        self::$db->query('COMMIT');

        return ['status' => 'OK'];
    }

    /**
     * Create a new MyRadio_Track with the provided options.
     *
     * @param array $options
     *                       title (required): Title of the track.
     *                       artist (required): (string) Artist of the track.
     *                       recordid (required): (int) Album of track.
     *                       duration (required): Duration of the track, in seconds
     *                       number: Position of track on album
     *                       genre: Character code genre of track
     *                       intro: Length of track intro, in seconds
     *                       clean: 'y' yes, 'n' no, 'u' unknown lyric cleanliness status
     *                       digitised: boolean digitised status
     *
     * @return MyRadio_Track a shiny new MyRadio_Track with the provided options
     *
     * @throws MyRadioException
     */
    public static function create($options)
    {
        self::wakeup();

        $required = ['title', 'artist', 'recordid', 'duration'];
        foreach ($required as $require) {
            if (empty($options[$require])) {
                throw new MyRadioException($require.' is required to create a Track.', 400);
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
        //Not digitised, and format to t/f
        if (empty($options['digitised'])) {
            $options['digitised'] = 'f';
        } else {
            $options['digitised'] = $options['digitised'] ? 't' : 'f';
        }

        $result = self::$db->query(
            'INSERT INTO rec_track (number, title, artist, length, genre, intro, clean, recordid, digitised, digitisedby, duration)
            VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11) RETURNING *',
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
                $options['duration'],
            ]
        );

        $data = self::$db->fetchOne($result);

        return new self($data);
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
            $new_dir = Config::$music_central_db_path.'/records/'.$album->getID();
            if (!is_dir($new_dir)) {
                mkdir($new_dir);
            }
            $new_path = $new_dir.'/'.$this->getID().'.'.$ext;
            if (!copy($this->getPath($ext), $new_path)) {
                throw new MyRadioException('Failed to move file from '.$this->getPath($ext).' to '.$new_path);
            }
            unlink($this->getPath($ext));
        }

        $this->record = $album->getID();
        self::$db->query('UPDATE rec_track SET recordid=$1 WHERE trackid=$2', [$album->getID(), $this->getID()]);

        $this->updateCacheObject();
    }

    public function setTitle($title)
    {
        if (empty($title)) {
            throw new MyRadioException('Track title must not be empty!', 400);
        }

        $this->title = $title;
        self::$db->query('UPDATE rec_track SET title=$1 WHERE trackid=$2', [$title, $this->getID()]);
        $this->updateCacheObject();
    }

    public function setArtist($artist)
    {
        if (empty($artist)) {
            throw new MyRadioException('Track artist must not be empty!');
        }

        $this->artist = $artist;
        self::$db->query('UPDATE rec_track SET artist=$1 WHERE trackid=$2', [$artist, $this->getID()]);

        $this->updateCacheObject();
    }

    public function setPosition($position)
    {
        $this->position = (int) $position;
        self::$db->query('UPDATE rec_track SET number=$1 WHERE trackid=$2', [$this->getPosition(), $this->getID()]);
        $this->updateCacheObject();
    }

    public function getPosition()
    {
        return $this->position;
    }

    public function setDuration($duration)
    {
        $this->duration = (int) $duration;
        self::$db->query(
            'UPDATE rec_track SET length=$1, duration=$2 WHERE trackid=$3',
            [
            CoreUtils::intToTime($this->getDuration()),
            $this->getDuration(),
            $this->getID(),
            ]
        );
        $this->updateCacheObject();
    }

    /**
     * Set the length of the track intro, in seconds.
     *
     * @param int
     *
     * @api POST
     */
    public function setIntro($duration)
    {
        $this->intro = (int) $duration;
        self::$db->query(
            'UPDATE rec_track SET intro=$1 WHERE trackid=$2',
            [
            CoreUtils::intToTime($this->intro),
            $this->getID(),
            ]
        );
        $this->updateCacheObject();
    }

    /**
     * Returns all Tracks that are marked as digitsed in the library.
     *
     * @return MyRadio_Track[] An array of digitised Tracks
     */
    public static function getAllDigitised()
    {
        self::initDB();
        $result = self::$db->fetchColumn(self::BASE_TRACK_SQL.' WHERE digitised=\'t\'');

        $tracks = [];
        foreach ($result as $row) {
            $tracks[] = new self($row);
        }

        return $tracks;
    }

    /**
     * Returns the physical path to the Track.
     *
     * @param string $format Optional file extension - at time of writing this could me "mp3", "ogg" or "mp3.orig"
     *
     * @return string path to Track file
     */
    public function getPath($format = 'mp3')
    {
        return Config::$music_central_db_path.'/records/'.$this->getAlbum()->getID().'/'.$this->getID().'.'.$format;
    }

    /**
     * Returns whether this track's physical file exists.
     *
     * @param string $format Optional file extension - at time of writing this could me "mp3", "ogg" or "mp3.orig"
     *
     * @return bool If the file exists
     */
    public function checkForAudioFile($format = 'mp3')
    {
        return file_exists($this->getPath($format));
    }

    /**
     * Queries the last.fm API to find information about a track with the given title/artist combination.
     *
     * @param string $title  track title
     * @param string $artist track artist
     *
     * @return array album: MyRadio_Album object matching the input
     *               position: The track number on the album
     *               duration: The length of the track, in seconds
     */
    public static function getAlbumDurationAndPositionFromLastfm($title, $artist)
    {
        $details = json_decode(
            file_get_contents(
                'https://ws.audioscrobbler.com/2.0/?method=track.getInfo&api_key='
                .Config::$lastfm_api_key
                .'&artist='.urlencode($artist)
                .'&track='.urlencode(str_replace(' (Radio Edit)', '', $title))
                .'&format=json'
            ),
            true
        );

        if (!isset($details['track']['album'])) {
            //Send some defaults for album info
            return [
                'album' => MyRadio_Album::findOrCreate(Config::$short_name.' Downloads '.date('Y'), Config::$short_name),
                'position' => 0,
                'duration' => intval($details['track']['duration'] / 1000),
            ];
        }

        return [
            'album' => MyRadio_Album::findOrCreate($details['track']['album']['title'], $details['track']['album']['artist']),
            'position' => (int) $details['track']['album']['@attr']['position'],
            'duration' => intval($details['track']['duration'] / 1000),
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
     * @todo   Last.fm API Rate limit checks
     *
     * @return MyRadio_Track[]
     */
    public function getSimilar()
    {
        if (empty($this->lastfm_similar)) {
            $data = json_decode(
                file_get_contents(
                    'https://ws.audioscrobbler.com/2.0/?method=track.getSimilar&api_key='
                    .Config::$lastfm_api_key
                    .'&track='.urlencode($this->getTitle())
                    .'&artist='.urlencode($this->getArtist())
                    .'&limit=50&format=json'
                ),
                true
            );

            if (!is_array($data['similartracks']['track'])) {
                trigger_error($this.' had an empty Similar Tracks result.');

                return [];
            }
            foreach ($data['similartracks']['track'] as $r) {
                if ($r['match'] >= 0.25) {
                    //Try to find an exact match
                    $c = self::findByOptions(
                        [
                            'title' => $r['name'],
                            'artist' => $r['artist']['name'],
                            'limit' => 1,
                            'digitised' => true,
                            'precise' => true,
                        ]
                    );
                    //Try to find a not-so-exact match
                    if (empty($c)) {
                        $c = self::findByOptions(
                            [
                                'title' => $r['name'],
                                'artist' => $r['artist']['name'],
                                'limit' => 1,
                                'digitised' => true,
                                'precise' => false,
                            ]
                        );
                    }
                    //If match found, add track to Similar list
                    if (!empty($c)) {
                        $this->lastfm_similar[] = $c[0]->getID();
                    }
                }
            }

            $this->updateCacheObject();
        }

        return self::resultSetToObjArray($this->lastfm_similar);
    }

    /**
     * Returns whether the Track is iTones Blacklisted.
     *
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
            $this->updateCacheObject();
        }

        return $this->itones_blacklist;
    }

    /**
     * Returns various numbers that look pretty on a graph, which concern the Central Music Library.
     *
     * The format is compatible with Google Charts.
     *
     * @return array
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
            ['Unverified Metadata', $num_unverified],
        ];
    }
}
