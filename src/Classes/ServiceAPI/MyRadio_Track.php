<?php

/**
 * This file provides the MyRadio_Track class for MyRadio.
 */
namespace MyRadio\ServiceAPI;

use MyRadio\Config;
use MyRadio\MyRadioEmail;
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
     * The start time of an ending segment to the track, in seconds.
     *
     * @var int
     */
    private $outro;

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
     * The time when this track was last edited.
     *
     * @var int
     */
    private $last_edited_time;

    /**
     * The member who last edited this track.
     *
     * @var int
     */
    private $last_edited_memberid;

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
     *                      outro string HH:ii:ss
     *                      length string HH:ii:ss
     *                      duration int
     *                      number int
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
        $this->digitisedby = empty($result['digitisedby']) ?
            null : (int) $result['digitisedby'];
        $this->last_edited_time = empty($result['last_edited_time']) ?
            null : $result['last_edited_time'];
        $this->last_edited_memberid = empty($result['last_edited_memberid']) ?
            null : (int) $result['last_edited_memberid'];
        $this->genre = $result['genre'];
        $this->intro = strtotime('1970-01-01 '.$result['intro'].'+00');
        $this->outro = strtotime('1970-01-01 '.$result['outro'].'+00');
        $this->length = $result['length'];
        $this->duration = (int) $result['duration'];
        $this->number = (int) $result['number'];
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
        $sql = 'SELECT * FROM public.rec_track WHERE trackid=$1 LIMIT 1';
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
            new MyRadioFormField(
                'album',
                MyRadioFormField::TYPE_ALBUM,
                [
                    'label' => 'Album',
                    'explanation' => 'This must be an existing album in our system.'
                ]
            )
        )->addField(
            new MyRadioFormField(
                'position',
                MyRadioFormField::TYPE_NUMBER,
                [
                    'label' => 'Position',
                    'explanation' => 'The track number on the album.'
                ]
            )
        )->addField(
            new MyRadioFormField(
                'intro',
                MyRadioFormField::TYPE_NUMBER,
                [
                    'label' => 'Intro',
                    'explanation' => 'The track intro end time in seconds.'
                ]
            )
        )->addField(
            new MyRadioFormField(
                'outro',
                MyRadioFormField::TYPE_NUMBER,
                [
                    'label' => 'Outro',
                    'explanation' => 'The track outro start time in seconds.'
                ]
            )
        )->addField(
            new MyRadioFormField(
                'clean',
                MyRadioFormField::TYPE_SELECT,
                [
                    'options' => array_merge(
                        [['text' => 'Please select...', 'disabled' => true]],
                        self::getCleanOptions()
                    ),
                    'label' => 'Clean/Explicit/Unknown'
                ]
            )
        )->addField(
            new MyRadioFormField(
                'genre',
                MyRadioFormField::TYPE_SELECT,
                [
                    'options' => array_merge(
                        [['text' => 'Please select...', 'disabled' => true]],
                        self::getGenres()
                    ),
                    'label' => 'Genre'
                ]
            )
        )->addField(
            new MyRadioFormField(
                'digitised',
                MyRadioFormField::TYPE_CHECK,
                [
                    'label' => 'Digitised',
                    'required' => false
                ]
            )
        )->addField(
            new MyRadioFormField(
                'digitisedby',
                MyRadioFormField::TYPE_MEMBER,
                [
                    'label' => 'Digitised By',
                    'explanation' => 'The person who uploaded the track.',
                    'enabled' => false,
                    'required' => false
                ]
            )
        )->addField(
            new MyRadioFormField(
                'blacklisted',
                MyRadioFormField::TYPE_CHECK,
                [
                    'label' => 'Blacklisted',
                    'required' => false,
                    'explanation' => 'If the track is banned from playing on Jukebox.'
                ]
            )
        )->addField(
            new MyRadioFormField(
                'last_edited_separator',
                MyRadioFormField::TYPE_SECTION,
                [
                    'label' => 'Edit History'
                ]
            )
        )->addField(
            new MyRadioFormField(
                'last_edited_time',
                MyRadioFormField::TYPE_TEXT,
                [
                    'label' => 'Last Edited',
                    'required' => false,
                    'explanation' => 'The time someone last submitted this form for this track.',
                    'enabled' => false
                ]
            )
        )->addField(
            new MyRadioFormField(
                'last_edited_memberid',
                MyRadioFormField::TYPE_MEMBER,
                [
                    'label' => 'Last Edited By',
                    'required' => false,
                    'explanation' => 'The member that last submitted this form for this track.',
                    'enabled' => false
                ]
            )
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
                    'album' => $this->getAlbum(),
                    'position' => $this->getPosition(),
                    'intro' => $this->getIntro(),
                    'outro' => $this->getOutro(),
                    'clean' => $this->getClean(),
                    'genre' => $this->getGenre(),
                    'digitised' => $this->getDigitised(),
                    'digitisedby' => $this->getDigitisedBy(),
                    'blacklisted' => $this->isBlacklisted(),
                    'last_edited_time' => $this->getLastEditedTime() === null ? null :
                        CoreUtils::happyTime($this->getLastEditedTime()),
                    'last_edited_memberid' => $this->getLastEditedMemberID(),
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
     * Get the outro start-time of the Track, in seconds.
     *
     * @return int
     */
    public function getOutro()
    {
        return $this->outro;
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
     * Get the genre of the Track.
     *
     * @return char
     */
    public function getGenre()
    {
        return $this->genre;
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

    /**
     * Get the user who digitised the track
     * @param MyRadio_User $digitisedby The user who digitised the track.
     */
    public function getDigitisedBy()
    {
        if ($this->digitisedby === null) {
            return;
        } else {
            return MyRadio_User::getInstance($this->digitisedby);
        }
    }

    /**
     * Get the last time a user edited the track.
     *
     * @return bool
     */
    public function getLastEditedTime()
    {
        return $this->last_edited_time;
    }

    /**
     * Get the user who last edited the track.
     *
     * @return MyRadio_User $last_edited_memberid The user who last edited the track.
     */
    public function getLastEditedMemberID()
    {
        if ($this->last_edited_memberid === null) {
            return;
        } else {
            return MyRadio_User::getInstance($this->last_edited_memberid);
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
            'UPDATE rec_track SET digitised=$1 WHERE trackid=$2',
            $digitised ? ['t', $this->getID()] : ['f', $this->getID()]
        );
        $this->updateCacheObject();
    }

    /**
     * Update the user who digitised the track
     * @param MyRadio_User $digitisedby The user who digitised the track.
     */
    public function setDigitisedBy($digitisedby)
    {
        $this->digitisedby = $digitisedby->getID();
        self::$db->query(
            'UPDATE rec_track SET digitisedby=$1 WHERE trackid=$2',
            [$digitisedby->getID(), $this->getID()]
        );
        $this->updateCacheObject();
    }

    /**
     * Update when a user last edited the track info.
     *
     * @param bool $digitised
     */
    public function setLastEdited()
    {
        $this->last_edited_time = CoreUtils::getTimestamp();
        $this->last_edited_memberid = $_SESSION['memberid'];
        self::$db->query(
            'UPDATE rec_track SET last_edited_time=$1, last_edited_memberid=$2 WHERE trackid=$3',
            [
                $this->last_edited_time,
                $this->last_edited_memberid,
                $this->getID()
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
     * @param array $mixins Mixins. Currently unused.
     * @return array
     * @todo Expand the information this returns
     */
    public function toDataSource($mixins = [])
    {
        return [
            'title' => $this->getTitle(),
            'artist' => $this->getArtist(),
            'type' => 'central', //Tells NIPSWeb Client what this item type is
            'album' => $this->getAlbum()->toDataSource(),
            'trackid' => $this->getID(),
            'length' => $this->getLength(),
            'intro' => $this->getIntro(),
            'outro' => $this->getOutro(),
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
     * Report this track as explicit.
     *
     * Do not confuse with {@link setClean} - this method will set it as explicit, and send
     * an email to various people informing them of it. If you don't want that to happen,
     * don't use this.
     */
    public function reportExplicit()
    {
        if ($this->getClean() === 'n') {
            throw new MyRadioException('This is already marked explicit.', 400);
        }

        $this->setClean('n');

        $currentUser = MyRadio_User::getCurrentOrSystemUser();

        $title = htmlspecialchars($this->getTitle());
        $artist = htmlspecialchars($this->getArtist());
        if (empty($currentUser->getNName()) == True)  {
            $userName = htmlspecialchars($currentUser->getFName() . ' ' . $currentUser->getSName());
        } else {
            $userName = htmlspecialchars($currentUser->getFName() . ' "' . $currentUser->getNName() . '" ' . $currentUser->getSName());
        }
        $editUrl = URLUtils::makeURL('Library', 'editTrack', ['trackid' => $this->getID()]);
        MyRadioEmail::sendEmailToList(
            MyRadio_List::getByName('playlisting'),
            'Track Reported Explicit',
            <<<EOF
Hi,

The song "$title" by $artist has been reported as explicit by $userName.

Please double-check this, and mark it as clean if this is in error. You can edit the track <a href="$editUrl">here</a>.

Thanks,
MyRadio Music Library Robot
EOF
            ,
            null
        );
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
     * Search for tracks in the library.
     *
     * @param string $title Only return tracks matching this title
     * @param string $artist Only return tracks matching this artist
     * @param integer $recordid Only return tracks in this album
     * @param boolean $digitised Only return tracks that are digitised. If false, return any. Default true.
     * @param enum $clean Only return tracks with the given cleanliness (y = clean, n = explicit, u = unknown)
     * @param boolean $precise Only return exact matches for title and artist. Defaults to fuzzy search.
     * @param integer $limit Search only returns the default config number of results by default, this overrides that.
     * @param enum $sort Sort order. Possible values: "id" (default), "title", "random". Random will not paginate well.
     * @param string $itonesplaylistid Managed playlist id to return,
     * for example 'breakfast' will return all tracks from the breakfast playlist.
     */
    public static function search(
        $title = null,
        $artist = null,
        $recordid = null,
        $digitised = true,
        $clean = null,
        $precise = false,
        $limit = null,
        $sort = null,
        $itonesplaylistid = null
    ) {
        if ($clean !== null && $clean !== 'u' && $clean !== 'y' && $clean !== 'n') {
            throw new MyRadioException('Valid values for clean are u, y and n.');
        }

        if ($sort !== null && $sort !== 'id' && $sort !== 'title' && $sort !== 'random') {
            throw new MyRadioException('Valid values for sort are id, title and random.');
        }

        $options = [
            'title' => $title,
            'artist' => $artist,
            'recordid' => empty($recordid) ? null : (int)$recordid,
            'digitised' => filter_var($digitised, FILTER_VALIDATE_BOOLEAN),
            'clean' => $clean,
            'precise' => filter_var($precise, FILTER_VALIDATE_BOOLEAN),
            'itonesplaylistid' => $itonesplaylistid
        ];
        if ($limit != null) {
            $options['limit'] = $limit;
        }
        if ($sort === 'id') {
            $options['idsort'] = true;
        }
        if ($sort === 'title') {
            $options['titlesort'] = true;
        }
        if ($sort === 'random') {
            $options['random'] = true;
        }

        return self::findByOptions($options);
    }

    /**
     * Not for use via the Swagger API. See /track/search instead.
     *
     * @swagger ignore
     * @param array $options One or more of the following:
     *                       title: String title of the track
     *                       artist: String artist name of the track
     *                       digitised: If true, only return digitised tracks. If false, return any.
     *                       itonesplaylistid: Tracks that are members of the iTones_Playlist id
     *                       limit: Maximum number of items to return. 0 = No Limit. start,limit can also be used.
     *                       recordid: int Record id
     *                       lastfmverified: Boolean whether or not verified with Last.fm Fingerprinter. Default any.
     *                       random: If true, sort randomly
     *                       idsort: If true, sort by trackid (default)
     *                       titlesort: If true, sort by title
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
        if (isset($options['random']) && isset($options['titlesort'])) {
            if (!$options['random'] && !$options['titlesort']) {
                $options['idsort'] = true;
            }
        } elseif (isset($options['random'])) {
            if (!$options['random']) {
                $options['idsort'] = true;
                $options['titlesort'] = false;
            }
        } elseif (isset($options['titlesort'])) {
            if (!$options['titlesort']) {
                $options['idsort'] = true;
                $options['random'] = false;
            }
        } else {
            $options['idsort'] = true;
            $options['random'] = false;
            $options['titlesort'] = false;
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
        if (empty($options['titlesort'])) {
            $options['titlesort'] = null;
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
            &&  $options['idsort']
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
            .' ' .$firstop
            .' rec_track.artist ILIKE $1 || $3 || $1)'
            .($options['album'] ? ' AND rec_record.title ILIKE $1 || $'.$album_param.' || $1' : '')
            .($options['digitised'] ? ' AND digitised=\'t\'' : '')
            .($options['lastfmverified'] === true ? ' AND lastfm_verified=\'t\'' : '')
            .($options['lastfmverified'] === false ? ' AND lastfm_verified=\'f\'' : '')
            .($options['nocorrectionproposed'] === true ? ' AND trackid NOT IN (
            SELECT trackid FROM public.rec_trackcorrection WHERE state=\'p\')' : '')
            .($options['clean'] != null ? ' AND clean=$'.$clean_param : '')
            .($options['custom'] !== null ? ' AND '.$options['custom'] : '')
            .($options['random'] ? ' ORDER BY RANDOM()' : '')
            .($options['idsort'] ? ' ORDER BY trackid' : '')
            .($options['titlesort'] ? ' ORDER BY rec_track.title' : '')
            .($options['limit'] == 0 ? '' : ' LIMIT $'.$limit_param),
            $sql_params
        );

        $response = [];
        foreach ($result as $trackid) {
            if ($options['recordid'] !== null && $trackid['recordid'] != $options['recordid']) {
                continue;
            }
            $response[] = self::getInstance($trackid['trackid']);
        }

        //Intersect with iTones if necessary, then return
        return empty($options['itonesplaylistid']) ?
            $response :
            array_intersect($response, iTones_Playlist::getInstance($options['itonesplaylistid'])->getTracks());
    }

    /**
     * This method processes an unknown mp3 file that has been uploaded, storing a temporary copy of the file in /tmp/,
     * then attempting to identify the track by querying it against the last.fm database.
     *
     * @param type $tmp_path
     */
    public static function cacheAndIdentifyUploadedTrack($tmp_path)
    {
        if (!isset($_SESSION['myradio_nipsweb_file_cache_counter'])) {
            $_SESSION['myradio_nipsweb_file_cache_counter'] = 0;
        }
        if (!is_dir(Config::$audio_upload_tmp_dir)) {
            mkdir(Config::$audio_upload_tmp_dir);
        }

        $filename = session_id().'-'.++$_SESSION['myradio_nipsweb_file_cache_counter'].'.mp3';

        if (!move_uploaded_file($tmp_path, Config::$audio_upload_tmp_dir.'/'.$filename)) {
            throw new MyRadioException('Failed to move uploaded track to tmp directory.', 500);
        }

        $getID3 = new \getID3();
        $fileInfo = $getID3->analyze(Config::$audio_upload_tmp_dir.'/'.$filename);
        $getID3_lib = new \getID3_lib();
        $getID3_lib->CopyTagsToComments($fileInfo);

        // File quality checks
        if ($fileInfo['audio']['bitrate'] < 192000) {
            return [
                'status' => 'FAIL',
                'message' => 'Bitrate is below 192kbps',
                'fileid' => $filename,
                'bitrate' => $fileInfo['audio']['bitrate']
            ];
        }
        if (strpos($fileInfo['audio']['channelmode'], 'stereo') === false) {
            return [
                'status' => 'FAIL',
                'message' => 'Item is not stereo',
                'fileid' => $filename,
                'channelmode' => $fileInfo['audio']['channelmode']
            ];
        }

        $analysis['status'] = 'INFO';
        $analysis['message'] = 'Currently editing track information for';
        $analysis['submittable'] = true;
        $analysis['fileid'] = $filename;
        $analysis['analysis']['title'] = $fileInfo['comments_html']['title'];
        $analysis['analysis']['artist'] = $fileInfo['comments_html']['artist'];
        $analysis['analysis']['album'] = $fileInfo['comments_html']['album'];

        //Remove total tracks in album from the track_number tag.
        $trackNo = explode("/", $fileInfo['comments_html']['track_number'][0], 2)[0];
        $analysis['analysis']['position'] = (string)$trackNo;

        $trackName = implode("", $fileInfo['comments_html']['title']);
        $analysis['analysis']['explicit'] = !!stripos($trackName, 'explicit');

        return $analysis;
    }

    /**
     * Attempts to identify an MP3 file against the last.fm database.
     *
     * !This method requires the external lastfm-fpclient application to be installed on the server. A FreeBSD build
     * with URY's API key and support for -json can be found in the fpclient.git URY Git repository.
     *
     ***** Since LastFM was removed from the central track uploader, this code MAY not be used anymore.
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
                return [[
                    'title' => $lastfm['tracks']['track']['name'],
                    'artist' => $lastfm['tracks']['track']['artist']['name'],
                    'rank' => $lastfm['tracks']['track']['@attr']['rank'],
                ]];
            }

            $tracks = [];
            if (empty($lastfm['tracks']['track'])) {
                return [];
            }

            foreach ($lastfm['tracks']['track'] as $track) {
                $tracks[] = [
                    'title' => $track['name'],
                    'artist' => $track['artist']['name'],
                    'rank' => $track['@attr']['rank']
                ];
            }

            return $tracks;
        }
    }

    /**
      * Pay special attention to the tri-state value of explicit. False and null are different things.
    */
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
                $track->setDigitisedBy(MyRadio_User::getInstance($_SESSION['memberid']));
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
        try {
            CoreUtils::encodeTrack($tmpfile, $dbfile);
        } catch (MyRadioException $e) {
            return ['status' => 'FAIL', 'error' => $e->getMessage()];
        }

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
     *                       outro: Start time of track outro, in seconds
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
        //Other Genre (can be automatically updated later on the weekly genres updater)
        if (empty($options['genre'])) {
            $options['genre'] = 'o';
        }
        //No intro
        if (empty($options['intro'])) {
            $options['intro'] = 0;
        }
        //No outro
        if (empty($options['outro'])) {
            $options['outro'] = 0;
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

        $data = self::$db->fetchOne(
            'INSERT INTO rec_track (number, title, artist, length, genre, intro, outro,
            clean, recordid, digitised, digitisedby, duration, last_edited_time, last_edited_memberid)
            VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13, $11) RETURNING *',
            [
                $options['number'],
                trim($options['title']),
                trim($options['artist']),
                CoreUtils::intToTime($options['duration']),
                $options['genre'],
                CoreUtils::intToTime($options['intro']),
                CoreUtils::intToTime($options['outro']),
                $options['clean'],
                $options['recordid'],
                $options['digitised'],
                $_SESSION['memberid'],
                $options['duration'],
                CoreUtils::getTimestamp()
            ]
        );

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
        $this->number = (int) $position;
        self::$db->query('UPDATE rec_track SET number=$1 WHERE trackid=$2', [$this->getPosition(), $this->getID()]);
        $this->updateCacheObject();
    }

    public function getPosition()
    {
        return $this->number;
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

    public function setGenre($genre)
    {
        $this->genre = $genre;
        self::$db->query(
            'UPDATE rec_track SET genre=$1 WHERE trackid=$2',
            [
                $genre,
                $this->getID()
            ]
        );
        $this->updateCacheObject();
    }

    /**
     * Set the length of the track intro, in seconds.
     *
     * @param int $duration Duration of the intro
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
     * Set the start-time of the track outro, in seconds.
     *
     * @param int $start_time Start-time of the outro
     *
     * @api POST
     */
    public function setOutro($start_time)
    {
        $this->outro = (int) $start_time;
        self::$db->query(
            'UPDATE rec_track SET outro=$1 WHERE trackid=$2',
            [
            CoreUtils::intToTime($this->outro),
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
        $result = self::$db->fetchColumn('SELECT trackid FROM public.rec_track WHERE digitised=\'t\'');

        $tracks = [];
        foreach ($result as $row) {
            $tracks[] = self::getInstance($row);
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
                'album' => MyRadio_Album::findOrCreate(
                    Config::$short_name . ' Downloads ' . date('Y'),
                    Config::$short_name
                ),
                'position' => 0,
                'duration' => intval($details['track']['duration'] / 1000),
            ];
        }

        return [
            'album' => MyRadio_Album::findOrCreate(
                $details['track']['album']['title'],
                $details['track']['album']['artist']
            ),
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

    public function setBlacklisted($blacklist)
    {
        if ($blacklist === true) {
            $this->itones_blacklist = true;
            self::$db->query(
                'INSERT INTO jukebox.track_blacklist
                (trackid) VALUES ($1)',
                [
                    $this->getID()
                ]
            );
            $this->updateCacheObject();
        } elseif ($blacklist === false && $this->isBlacklisted()) {
            $this->itones_blacklist = false;
            self::$db->query(
                'DELETE FROM jukebox.track_blacklist
                WHERE trackid = $1',
                [
                    $this->getID()
                ]
            );
            $this->updateCacheObject();
        }
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
        $num_digitised = (int) self::$db->fetchColumn(
            'SELECT COUNT(*) FROM public.rec_track WHERE digitised=\'t\''
        )[0];
        $num_undigitised = (int) self::$db->fetchColumn(
            'SELECT COUNT(*) FROM public.rec_track WHERE digitised=\'f\''
        )[0];

        $num_clean = (int) self::$db->fetchColumn('SELECT COUNT(*) FROM public.rec_track WHERE clean=\'y\'')[0];
        $num_unclean = (int) self::$db->fetchColumn('SELECT COUNT(*) FROM public.rec_track WHERE clean=\'n\'')[0];
        $num_cleanunknown = (int) self::$db->fetchColumn('SELECT COUNT(*) FROM public.rec_track WHERE clean=\'u\'')[0];

        $num_verified = (int) self::$db->fetchColumn(
            'SELECT COUNT(*) FROM public.rec_track WHERE digitised=\'t\' AND lastfm_verified=\'t\''
        )[0];
        $num_unverified = (int) self::$db->fetchColumn(
            'SELECT COUNT(*) FROM public.rec_track WHERE digitised=\'t\' AND lastfm_verified=\'f\''
        )[0];

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

    /**
     * Gets the track that's on air *right now*.
     *
     * @param string[] $sources which sources to accept tracklist data from (tracklist.source in db)
     * @param bool $allowOffAir Should whatever Jukebox is playing be included even when it's not on air.
     * Silly unless 'j' is passed in $sources
     * @return null|array
     */
    public static function getNowPlaying(
        $sources = ['b', 'm', 'o', 'w', 'a', 's', 'j', '1', '2', '4'],
        $allowOffAir = false
    ) {
        // Deal with the boolean coming through the API as a string,
        // and therefore is always true
        if ($allowOffAir == "false") {
            $allowOffAir = false;
        }

        // Start a transaction. We're gonna have some fun.
        self::$db->query('BEGIN');

        // Use repeatable read - to ensure that all queries in this TX read at the same "point in time"
        self::$db->query('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');

        // Fetch permissible source letters and filter sources to prevent nasties
        $allowedSources = self::$cache->get('MyRadio_Track:getNowPlaying:allowedSources');
        if (empty($allowedSources)) {
            $allowedSources = self::$db->fetchColumn('SELECT sourceid FROM tracklist.source', []);
            self::$cache->set('MyRadio_Track:getNowPlaying:allowedSources', $allowedSources, 86400);
        }
        $sources = array_intersect($sources, $allowedSources);
        // Turn into SQL-friendly string
        // Like implode, but it doesn't mess with precious numerical char types.
        $sourceStr = "";
        for ($i = 0; $i < count($sources); $i++) {
            $sourceStr = $sourceStr . "','" . $sources[$i];
        }

        // Get the last thing that was tracklisted - this is either jukebox or WebStudio
        // The 30 minutes check is to avoid having something linger for too long if WS forgets to end the tracklist
        $lastTracklisted = self::$db->fetchOne(
            'SELECT audiologid, timestart AT TIME ZONE \'Europe/London\' as timestart, trackid, track, artist, album
            FROM tracklist.tracklist
            LEFT OUTER JOIN tracklist.track_rec USING (audiologid)
            LEFT OUTER JOIN tracklist.track_notrec USING (audiologid)
            WHERE timestart <= NOW() AND timestart > (NOW() - interval \'30 minutes\') AND timestop IS NULL
            AND (state IS NULL OR state = \'c\'' .($allowOffAir ? ' OR state = \'o\'' : '') . ')
            AND source IN (\'' . $sourceStr . '\')
            ORDER BY timestart DESC
            LIMIT 1',
            []
        );

        // Check what's currently on air - if it's a physical studio or OB we'll need to check BAPS
        // We do this in SQL, rather than via MyRadio_Selector, to maintain transaction consistency
        if (in_array('b', $sources)) {
            $result = self::$db->fetchColumn(
                'SELECT action FROM public.selector WHERE time <= NOW()
                AND action >= 4 AND action <= 11
                ORDER BY time DESC
                LIMIT 1',
                []
            );
            $selAction = isset($result[0]) ? intval($result[0]) : 0;
            if ($selAction === 4 /* Studio 1 */ || $selAction === 5 /* Studio 2 */ || $selAction == 7 /* OB */) {
                // Ditto on the 30 minutes
                // The 30 *seconds* is to (hopefully) catch PFLs
                $lastBapsLogged = self::$db->fetchOne(
                    'SELECT audiologid, timeplayed AT TIME ZONE \'Europe/London\' AS timestart, trackid
                    FROM public.baps_audiolog
                    INNER JOIN public.baps_audio USING (audioid)
                    INNER JOIN tracklist.selbaps ON baps_audiolog.serverid = selbaps.bapsloc
                    WHERE selaction = $1
                    AND timestopped IS NULL
                    AND trackid IS NOT NULL
                    AND timeplayed <= (NOW() AT TIME ZONE \'Europe/London\' - interval \'30 seconds\')
                    AND timeplayed > (NOW() AT TIME ZONE \'Europe/London\' - interval \'30 minutes\')
                    ORDER BY timeplayed DESC
                    LIMIT 1
                    ',
                    [ $selAction ]
                );
                if (!empty($lastBapsLogged)) {
                    if (empty($lastTracklisted)
                        || strtotime($lastBapsLogged['timestart']) > strtotime($lastTracklisted['timestart'])
                    ) {
                        // Last BAPS entry is newer than last tracklist entry (if there is one).
                        $lastTracklisted = $lastBapsLogged;
                    }
                }
            }
        }
        // We're done querying
        self::$db->query('COMMIT');

        if (empty($lastTracklisted)) {
            // Nothing playing right now
            return null;
        } elseif (!empty($lastTracklisted['trackid'])) {
            // track_rec
            return [
                'track' => self::getInstance($lastTracklisted['trackid']),
                'start_time' => $lastTracklisted['timestart']
            ];
        } else {
            // track_notrec (manual tracklisting)
            // Double-check it was in the last five minutes
            if (strtotime($lastTracklisted['timestart']) > (time() - 300)) {
                return [
                    'track' => [
                        'title' => $lastTracklisted['track'],
                        'artist' => $lastTracklisted['artist'],
                        'album' => $lastTracklisted['album']
                    ],
                    'start_time' => $lastTracklisted['timestart']
                ];
            } else {
                return null;
            }
        }
    }

    /**
     * Returns a list of potential clean statuses, organised so
     * they can be used as a SELECT MyRadioFormField data source.
     */
    public static function getCleanOptions()
    {
        self::wakeup();

        return self::$db->fetchAll(
            'SELECT clean_code AS value, clean_descr AS text FROM public.rec_cleanlookup ORDER BY clean_descr ASC'
        );
    }

    /**
     * Returns a list of potential genres, organised so
     * they can be used as a SELECT MyRadioFormField data source.
     */
    public static function getGenres()
    {
        self::wakeup();

        return self::$db->fetchAll(
            'SELECT genre_code AS value, genre_descr AS text FROM public.rec_genrelookup ORDER BY genre_descr ASC'
        );
    }

    public static function getGraphQLTypeName()
    {
        return 'Track';
    }
}
