<?php
/**
 * Provides the Show class for MyRadio.
 */
namespace MyRadio\ServiceAPI;

use MyRadio\Config;
use MyRadio\Database;
use MyRadio\MyRadioException;
use MyRadio\MyRadio\CoreUtils;
use MyRadio\MyRadio\URLUtils;
use MyRadio\MyRadio\MyRadioForm;
use MyRadio\MyRadio\MyRadioFormField;
use MyRadio\ServiceAPI\MyRadio_User;
use MyRadio\ServiceAPI\MyRadio_Season;
use MyRadio\ServiceAPI\MyRadio_Scheduler;
use MyRadio\ServiceAPI\MyRadio_Timeslot;
use MyRadio\ServiceAPI\MyRadio_Term;

/**
 * The Show class is used to create, view and manupulate Shows within the new MyRadio Scheduler Format.
 *
 * @uses \Database
 */
class MyRadio_Show extends MyRadio_Metadata_Common
{
    const BASE_SHOW_SQL =
        'SELECT show_id,
            show.show_type_id,
            show.submitted,
            show.memberid AS owner,
            show.podcast_explicit::int,
            array_to_json(metadata.metadata_key_id) AS metadata_keys,
            array_to_json(metadata.metadata_value) AS metadata_values,
            array_to_json(image_metadata.image_metadata_key_id) AS image_metadata_keys,
            array_to_json(image_metadata.image_metadata_value) AS image_metadata_values,
            array_to_json(credits.credit_type_id) AS credit_types,
            array_to_json(credits.creditid) AS credits,
            array_to_json(genre.genre_id) AS genres,
            array_to_json(season.show_season_id) AS seasons,
            subtype.show_subtype_id as subtype_id
        FROM
            schedule.show
            NATURAL FULL JOIN
            (
                SELECT
                    show_id,
                    array_agg(metadata_key_id) AS metadata_key_id,
                    array_agg(metadata_value) AS metadata_value
                FROM schedule.show_metadata
                WHERE effective_from <= NOW()
                    AND (effective_to IS NULL OR effective_to >= NOW())
                    AND approvedid IS NOT NULL
                GROUP BY show_id
            ) AS metadata
            NATURAL FULL JOIN
            (
                SELECT
                    show_id,
                    array_agg(metadata_key_id) AS image_metadata_key_id,
                    array_agg(metadata_value) AS image_metadata_value
                FROM schedule.show_image_metadata
                WHERE effective_from <= NOW()
                    AND (effective_to IS NULL OR effective_to >= NOW())
                    AND approvedid IS NOT NULL
                GROUP BY show_id
            ) AS image_metadata
            NATURAL FULL JOIN
            (
                SELECT
                    show_id,
                    array_agg(credit_type_id) AS credit_type_id,
                    array_agg(creditid) AS creditid
                FROM schedule.show_credit
                WHERE effective_from <= NOW()
                    AND (effective_to IS NULL OR effective_to >= NOW())
                    AND approvedid IS NOT NULL
                GROUP BY show_id
            ) AS credits
            NATURAL FULL JOIN
            (
                SELECT
                    show_id,
                    array_agg(genre_id) AS genre_id
                FROM schedule.show_genre
                WHERE effective_from <= NOW()
                    AND (effective_to IS NULL OR effective_to >= NOW())
                    AND approvedid IS NOT NULL
                GROUP BY show_id
            ) AS genre
            NATURAL FULL JOIN
            (
                SELECT
                    show_id,
                    array_agg(show_season_id ORDER BY termid, submitted) AS show_season_id
                FROM schedule.show_season
                GROUP BY show_id
            ) AS season
            NATURAL FULL JOIN (
                SELECT show_subtype_id,
                       show_id
                FROM schedule.show_season_subtype
                GROUP BY show_subtype_id, show_id
            ) AS subtype';

    private $show_id;
    protected $owner;
    protected $credits = [];
    private $genres;
    private $show_type;
    private $submitted_time;
    private $season_ids;
    private $photo_url;
    private $podcast_explicit;
    private $subtype_id;

    /**
     * @param $result Array
     * show_id int
     * show_type_id int
     * submitted strtotimeable string
     * owner int
     * metadata_keys array[int]
     * metadata_values array[string]
     * image_metadata_keys array[int]
     * image_metadata_values array[string]
     * credit_types array[int]
     * credits array[int]
     * genres array[int]
     * seasons array[int]
     */
    protected function __construct($result)
    {
        $this->show_id = (int) $result['show_id'];

        //Deal with the easy fields
        $this->owner = (int) $result['owner'];
        $this->show_type = (int) $result['show_type_id'];
        $this->submitted_time = strtotime($result['submitted']);
        $this->podcast_explicit = (bool) $result['podcast_explicit'];
        $this->subtype_id = (int) $result['subtype_id'];

        $this->genres = json_decode($result['genres']);
        if ($this->genres === null) {
            $this->genres = [];
        }

        //Deal with the Credits arrays
        $credit_types = $result['credit_types'] !== null ? json_decode($result['credit_types']) : [];
        $credits = $result['credits'] !== null ? json_decode($result['credits']) : [];

        for ($i = 0; $i < sizeof($credits); ++$i) {
            if (empty($credits[$i])) {
                continue;
            }
            $this->credits[] = [
                'type' => (int) $credit_types[$i],
                'memberid' => $credits[$i],
                'User' => MyRadio_User::getInstance($credits[$i]),
            ];
        }

        //Deal with the Metadata arrays
        $metadata_types = json_decode($result['metadata_keys']);
        $metadata = json_decode($result['metadata_values']);
        if ($metadata_types === null) {
            $metadata_types = [];
        }
        if ($metadata === null) {
            $metadata = [];
        }

        for ($i = 0; $i < sizeof($metadata); ++$i) {
            if (self::isMetadataMultiple($metadata_types[$i])) {
                //Multiples should be an array
                $this->metadata[$metadata_types[$i]][] = $metadata[$i];
            } else {
                $this->metadata[$metadata_types[$i]] = $metadata[$i];
            }
        }

        //Deal with Show Photo
        /*
         * @todo Support general photo attachment?
         */
        $this->photo_url = Config::$default_person_uri;
        if ($result['image_metadata_values'] !== null) {
            $image_metadata = json_decode($result['image_metadata_values']);
            $this->photo_url = Config::$public_media_uri.'/'.$image_metadata[0];
        }

        //Get information about Seasons
        if ($result['seasons'] !== null) {
            $this->season_ids = json_decode($result['seasons']);
        } else {
            $this->season_ids = [];
        }
    }

    protected static function factory($showid)
    {
        $sql = self::BASE_SHOW_SQL.' WHERE show_id=$1';
        $result = self::$db->fetchOne($sql, [$showid]);

        if (empty($result)) {
            throw new MyRadioException("The specified Show (show id: " . $showid . ") does not seem to exist", 404);
        }

        return new self($result);
    }

    /**
     * Creates a new MyRadio Show and returns an object representing it.
     *
     * @param array $params An assoc array (possibly decoded from JSON),
     * taking a format generally based on what toDataSource produces
     * Properties may be "genres" (["Jazz", ...], "credits" ([["memberid": 7449, "typeid": 1], ...]),
     * location or any valid metadata key.
     * The title/description metadata keys, and the credits key, are all required.
     * e.g. Set upload_state: "Requested" to set this show to be uploaded to Mixclouder after broadcast.
     *
     * As this is the initial creation, all data are <i>approved</i> by the submitter
     * so the show has some initial values
     *
     * @throws MyRadioException
     */
    public static function create($params = [])
    {
        //Validate input
        $required = ['title', 'description', 'credits', 'subtype'];
        foreach ($required as $field) {
            if (!isset($params[$field])) {
                throw new MyRadioException('You must provide ' . $field, 400);
            }
        }

        self::initDB();

        //Get or set the show type id
        if (empty($params['showtypeid'])) {
            $rtype = self::$db->fetchColumn('SELECT show_type_id FROM schedule.show_type WHERE name=\'Show\'');
            if (empty($rtype[0])) {
                throw new MyRadioException('There is no Show ShowType Available!', MyRadioException::FATAL);
            }
            $params['showtypeid'] = (int) $rtype[0];
        }

        if (!isset($params['genres'])) {
            $params['genres'] = [];
        }
        if (!isset($params['tags'])) {
            $params['tags'] = '';
        }

        // Support API calls where there is no session.
        // @todo should this be system_user?
        if (!empty($_SESSION['memberid'])) {
            $creator = $_SESSION['memberid'];
        } else {
            $creator = $params['credits']['memberid'][0];
        }

        //We're all or nothing from here on out - transaction time
        self::$db->query('BEGIN');

        //Add the basic info, getting the show id

        $result = self::$db->fetchColumn(
            'INSERT INTO schedule.show (show_type_id, submitted, memberid, podcast_explicit)
            VALUES ($1, NOW(), $2, $3::boolean) RETURNING show_id',
            [
                $params['showtypeid'],
                $creator,
                (isset($params['podcast_explicit']) && $params['podcast_explicit']) ? 1 : 0
            ],
            true
        );
        if (empty($result)) {
            throw new MyRadioException('Inserting show record failed!', 500);
        }
        $show_id = $result[0];

        //Right, set the title and description next
        foreach (['title', 'description'] as $key) {
            self::$db->query(
                'INSERT INTO schedule.show_metadata
                (metadata_key_id, show_id, metadata_value, effective_from, memberid, approvedid)
                VALUES ($1, $2, $3, NOW(), $4, $4)',
                [self::getMetadataKey($key), $show_id, $params[$key], $creator],
                true
            );
        }

        //Genre time powers activate!
        if (!is_array($params['genres'])) {
            $params['genres'] = [$params['genres']];
        }
        foreach ($params['genres'] as $genre) {
            if (!is_numeric($genre)) {
                continue;
            }
            self::$db->query(
                'INSERT INTO schedule.show_genre (show_id, genre_id, effective_from, memberid, approvedid)
                VALUES ($1, $2, NOW(), $3, $3)',
                [$show_id, $genre, $creator],
                true
            );
        }

        // Explode the tags
        $tags = CoreUtils::explodeTags($params['tags']);
        foreach ($tags as $tag) {
            self::$db->query(
                'INSERT INTO schedule.show_metadata
                (metadata_key_id, show_id, metadata_value, effective_from, memberid, approvedid)
                VALUES ($1, $2, $3, NOW(), $4, $4)',
                [self::getMetadataKey('tag'), $show_id, $tag, $creator],
                true
            );
        }

        //Set a location
        if (empty($params['location'])) {
            /*
             * Hardcoded default to Studio 1
             * @todo Location support
             */
            $params['location'] = 1;
        }
        self::$db->query(
            'INSERT INTO schedule.show_location
            (show_id, location_id, effective_from, memberid, approvedid)
            VALUES ($1, $2, NOW(), $3, $3)',
            [
                $show_id,
                $params['location'],
                $creator,
            ],
            true
        );

        // Subtype too
        self::$db->query(
            'INSERT INTO schedule.show_season_subtype
        (show_id, show_subtype_id, effective_from)
        VALUES ($1, (SELECT show_subtype_id FROM schedule.show_subtypes WHERE show_subtypes.class = $2), NOW())',
            [$show_id, $params['subtype']]
        );

        //And now all that's left is who's on the show
        for ($i = 0; $i < sizeof($params['credits']['memberid']); ++$i) {
            //Skip blank entries
            if (empty($params['credits']['memberid'][$i])) {
                continue;
            }
            // Both a memberid and a User object are valid here.
            // This is icky and should be fixed.
            if (is_numeric($params['credits']['memberid'][$i])) {
                $member = MyRadio_User::getInstance($params['credits']['memberid'][$i]);
            } else {
                $member = $params['credits']['memberid'][$i];
            }
            self::$db->query(
                'INSERT INTO schedule.show_credit
                (show_id, credit_type_id, creditid, effective_from, memberid, approvedid)
                VALUES ($1, $2, $3, NOW(), $4, $4)',
                [
                    $show_id,
                    (int) $params['credits']['credittype'][$i],
                    $member->getID(),
                    $creator,
                ],
                true
            );
        }

        //Actually commit the show to the database!
        self::$db->query('COMMIT');

        $show = self::factory($show_id);

        /*
         * Enable mixcloud upload if requested
         */
        if ($params['mixclouder']) {
            $show->setMeta('upload_state', 'Requested');
        }

        return $show;
    }

    public static function getForm()
    {
        return (
            new MyRadioForm(
                'sched_show',
                'Scheduler',
                'editShow',
                [
                    'debug' => true,
                    'title' => 'Scheduler',
                    'subtitle' => 'Create a Show'
                ]
            )
        )->addField(
            new MyRadioFormField('grp-basics', MyRadioFormField::TYPE_SECTION, ['label' => 'About My Show'])
        )->addField(
            new MyRadioFormField(
                'title',
                MyRadioFormField::TYPE_TEXT,
                [
                    'explanation' => 'Enter a name for your new show. Try and make it unique.',
                    'label' => 'Show Name',
                ]
            )
        )->addField(
            new MyRadioFormField(
                'description',
                MyRadioFormField::TYPE_BLOCKTEXT,
                [
                    'explanation' => 'Describe your show as best you can. This goes on the public-facing website.',
                    'label' => 'Description',
                ]
            )
        )->addField(
            new MyRadioFormField(
                'genres',
                MyRadioFormField::TYPE_SELECT,
                [
                    'options' => array_merge(
                        [['text' => 'Please select...', 'disabled' => true]],
                        MyRadio_Scheduler::getGenres()
                    ),
                    'label' => 'Genre',
                    'explanation' => 'What type of music do you play, if any?',
                ]
            )
        )->addField(
            new MyRadioFormField(
                'subtype',
                MyRadioFormField::TYPE_SELECT,
                [
                    'options' => MyRadio_ShowSubtype::getOptions(),
                    'label' => 'Subtype',
                    'explanation' => 'Select the subtype for this show (speech, music, news, etc.)'
                    . ' If unsure, leave as Regular.'
                ]
            )
        )->addField(
            new MyRadioFormField(
                'tags',
                MyRadioFormField::TYPE_TEXT,
                [
                    'label' => 'Tags',
                    'explanation' => 'A set of keywords to describe your show generally, seperated with commas.',
                ]
            )
        )->addField(
            new MyRadioFormField('grp-basics_close', MyRadioFormField::TYPE_SECTION_CLOSE)
        )->addField(
            new MyRadioFormField('grp-credits', MyRadioFormField::TYPE_SECTION, ['label' => 'Who\'s On My Show'])
        )->addField(
            new MyRadioFormField(
                'credits',
                MyRadioFormField::TYPE_TABULARSET,
                [
                    'label' => 'Credits',
                    'options' => [
                        new MyRadioFormField(
                            'memberid',
                            MyRadioFormField::TYPE_MEMBER,
                            [
                                'explanation' => '',
                                'label' => 'Member Name',
                            ]
                        ),
                        new MyRadioFormField(
                            'credittype',
                            MyRadioFormField::TYPE_SELECT,
                            [
                                'options' => array_merge(
                                    [['text' => 'Please select...', 'disabled' => true]],
                                    MyRadio_Scheduler::getCreditTypes()
                                ),
                                'explanation' => '',
                                'label' => 'Role',
                            ]
                        ),
                    ],
                ]
            )
        )->addField(
            new MyRadioFormField('grp-credits_close', MyRadioFormField::TYPE_SECTION_CLOSE)
        )->addField(
            new MyRadioFormField(
                'mixclouder',
                MyRadioFormField::TYPE_CHECK,
                [
                    'explanation' => 'If ticked, your shows will automatically be uploaded to mixcloud',
                    'label' => 'Enable Mixcloud',
                    'options' => ['checked' => true],
                    'required' => false,
                ]
            )
        )->addField(
            new MyRadioFormField(
                'podcast_explicit',
                MyRadioFormField::TYPE_CHECK,
                [
                    'required' => false,
                    'label' => 'Podcast contains explicit content',
                    'explanation' => 'Check this box if, and only if, this show is a podcast '
                        . 'and it contains explicit content. '
                        . 'Remember: explicit content is NEVER acceptable to broadcast!',
                    'options' => ['checked' => false],
                ]
            )
        );
    }

    public function getEditForm()
    {
        return self::getForm()
            ->setSubtitle('Edit Show')
            ->editMode(
                $this->getID(),
                [
                    'title' => $this->getMeta('title'),
                    'description' => $this->getMeta('description'),
                    'genres' => $this->getGenre(),
                    'subtype' => $this->getSubtype()->getClass(),
                    'tags' => is_null($this->getMeta('tag')) ? null : implode(', ', $this->getMeta('tag')),
                    'credits.memberid' => array_map(
                        function ($ar) {
                            return $ar['User'];
                        },
                        $this->getCredits()
                    ),
                    'credits.credittype' => array_map(
                        function ($ar) {
                            return $ar['type'];
                        },
                        $this->getCredits()
                    ),
                    'mixclouder' => ($this->getMeta('upload_state') === 'Requested'),
                    'podcast_explicit' => $this->isPodcastExplicit()
                ]
            );
    }

    public static function getPhotoForm()
    {
        return (
            new MyRadioForm(
                'sched_showphoto',
                'Scheduler',
                'showPhoto',
                [
                    'debug' => true,
                    'title' => 'Update Show Photo',
                ]
            )
        )->addField(
            new MyRadioFormField(
                'show_id',
                MyRadioFormField::TYPE_HIDDEN
            )
        )->addField(
            new MyRadioFormField(
                'image_file',
                MyRadioFormField::TYPE_FILE,
                ['label' => 'Photo']
            )
        );
    }

    public function getNumberOfSeasons()
    {
        return sizeof($this->season_ids);
    }

    public function getAllSeasons()
    {
        $seasons = [];
        foreach ($this->season_ids as $season_id) {
            $seasons[] = MyRadio_Season::getInstance($season_id);
        }

        return $seasons;
    }

    /**
     * A simplified version of getAllTimeslots in MyRadio_Season.
     * This gets all the timeslots that were part of a show, but only returns a few values.
     * Note that start_time is a (PSQL) timestamp, not an epoch.
     *
     * @return Array timeslots with season_id, timeslot_id, start_time and duration
     */
    public function getAllTimeslots()
    {
        $sql =
            'SELECT
               show_season.show_season_id AS season_id,
               show_season_timeslot.show_season_timeslot_id AS timeslot_id,
               show_season_timeslot.start_time,
               show_season_timeslot.duration
             FROM schedule.show
             INNER JOIN
               schedule.show_season ON show.show_id = show_season.show_id
             INNER JOIN
               schedule.show_season_timeslot ON show_season.show_season_id = show_season_timeslot.show_season_id
             WHERE show.show_id = $1
             ORDER BY timeslot_id ASC';
        $result = self::$db->fetchAll($sql, [$this->show_id]);
        $timeslots = [];
        foreach ($result as $row) {
            $timeslots[] = [
                'season_id'   => (int) $row['season_id'],
                'timeslot_id' => (int) $row['timeslot_id'],
                'start_time'  => $row['start_time'],
                'duration'    => $row['duration'],
            ];
        }
        return $timeslots;
    }

    /**
     * Internally associates a Season with this Show.
     * Does not persist in database. Used for updating the cache.
     *
     * @param int $id
     */
    public function addSeason($id)
    {
        $this->season_ids[] = $id;
        $this->updateCacheObject();
    }

    public function getID()
    {
        return $this->show_id;
    }

    /**
     * Get the microsite URI.
     *
     * @return string
     */
    public function getWebpage()
    {
        return '/schedule/shows/'.$this->getID();
    }

    /**
     * Get the web url for the Show Photo.
     *
     * @return string
     */
    public function getShowPhoto()
    {
        return $this->photo_url;
    }

    /**
     * Returns the ID for the type of Show.
     *
     * @return int
     */
    public function getShowType()
    {
        return $this->show_type;
    }

    /**
     * Return the primary Genre. Shows generally only have one anyway.
     */
    public function getGenre()
    {
        return isset($this->genres[0]) ? $this->genres[0] : null;
    }

    /**
     * Gets the subtype for this show.
     *
     * Note that subtypes can be overridden per-season, so you should probably use MyRadio_Season->getSubtype().
     * @return MyRadio_ShowSubtype
     */
    public function getSubtype()
    {
        return MyRadio_ShowSubtype::getInstance($this->subtype_id);
    }

    /**
     * If this show is a podcast, does it contain explicit content?
     * @return bool
     */
    public function isPodcastExplicit()
    {
        return $this->podcast_explicit;
    }

    /**
     * Sets this show's subtype.
     *
     * @todo support effectiveFrom and effectiveTo
     * @param $subtypeId
     */
    public function setSubtype($subtypeId)
    {
        self::$db->query('UPDATE schedule.show_season_subtype SET show_subtype_id = $1 WHERE show_id = $1', [
            $subtypeId, $this->show_id
        ]);
    }

    /**
     * Sets this show's subtype by the subtype name.
     * @param $subtypeName
     */
    public function setSubtypeByName($subtypeName)
    {
        self::$db->query(
            'UPDATE schedule.show_season_subtype
            SET show_subtype_id = subtype.show_subtype_id
            FROM (SELECT show_subtype_id FROM schedule.show_subtypes WHERE show_subtypes.class = $2) AS subtype
            WHERE show_id = $1',
            [$this->show_id, $subtypeName]
        );
    }

    /**
    * Sets show photo
    *
    * @param string $tmp_path
    */
    public function setShowPhoto($tmp_path)
    {
        // The getimagesize() below can fail, so we want this in a transaction
        self::$db->query('BEGIN');
        $result = self::$db->fetchColumn(
            'INSERT INTO schedule.show_image_metadata (memberid, approvedid, metadata_key_id, metadata_value, show_id)
            VALUES ($1, $1, $2, $3, $4) RETURNING show_image_metadata_id',
            [
                MyRadio_User::getCurrentOrSystemUser()->getID(),
                self::getMetadataKey('player_image'),
                'tmp',
                $this->getID()
            ]
        )[0];
        
        $filetype = explode('/', getimagesize($tmp_path)['mime'])[1];
        $suffix = 'image_meta/ShowImageMetadata/'.$result.'.'.$filetype;
        $path = Config::$public_media_path.'/'.$suffix;
        move_uploaded_file($tmp_path, $path);
        chmod($path, 0644);

        self::$db->query(
            'UPDATE schedule.show_image_metadata SET effective_to=NOW()
            WHERE metadata_key_id=$1
            AND show_id=$2
            AND effective_from IS NOT NULL',
            [self::getMetadataKey('player_image'), $this->getID()]
        );

        self::$db->query(
            'UPDATE schedule.show_image_metadata SET effective_from=NOW(), metadata_value=$1
            WHERE show_image_metadata_id=$2',
            [$suffix, $result]
        );
        
        $this->photo_url = Config::$public_media_uri.'/'.$suffix;
        self::$db->query('COMMIT');
        $this->updateCacheObject();
    }

    /**
     * Sets a metadata key to the specified value.
     *
     * If any value is the same as an existing one, no action will be taken.
     * If the given key has is_multiple, then the value will be added as a new, additional key.
     * If the key does not have is_multiple, then any existing values will have effective_to
     * set to the effective_from of this value, effectively replacing the existing value.
     * This will *not* unset is_multiple values that are not in the new set.
     *
     * @param string $string_key     The metadata key
     * @param mixed  $value          The metadata value. If key is_multiple and value is an array, will create instance
     *                               for value in the array.
     * @param int    $effective_from UTC Time the metavalue is effective from. Default now.
     * @param int    $effective_to   UTC Time the metadata value is effective to. Default NULL (does not expire).
     */
    public function setMeta($string_key, $value, $effective_from = null, $effective_to = null)
    {
        $r = parent::setMetaBase(
            $string_key,
            $value,
            $effective_from,
            $effective_to,
            'schedule.show_metadata',
            'show_id'
        );
        $this->updateCacheObject();

        return $r;
    }

    /**
     * Sets the Genre, if it hasn't changed.
     *
     * @param int $genreid
     */
    public function setGenre($genreid)
    {
        if (empty($genreid)) {
            throw new MyRadioException('Genre cannot be empty!', 400);
        }
        if ($genreid != $this->getGenre()) {
            self::$db->query(
                'UPDATE schedule.show_genre SET effective_to=NOW() WHERE show_id=$1',
                [$this->getID()]
            );
            self::$db->query(
                'INSERT INTO schedule.show_genre (show_id, genre_id, effective_from, memberid, approvedid)
                VALUES ($1, $2, NOW(), $3, $3)',
                [$this->getID(), $genreid, MyRadio_User::getInstance()->getID()]
            );
            $this->genres = [$genreid];
            $this->updateCacheObject();
        }
    }

    /**
     * Sets this show's "Podcast explicit" status
     * @param $value bool
     */
    public function setPodcastExplicit($value)
    {
        self::$db->query(
            'UPDATE schedule.show SET podcast_explicit = $2::boolean WHERE show_id = $1',
            [$this->getID(), $value ? 1 : 0]
        );
        $this->updateCacheObject();
    }

    /**
     * Updates the list of Credits.
     *
     * Existing credits are kept active, ones that are not in the new list are set to effective_to now,
     * and ones that are in the new list but not exist are created with effective_from now.
     *
     * @param MyRadio_User[] $users       An array of Users associated.
     * @param int[]          $credittypes The relevant credittypeid for each User.
     */
    public function setCredits($users, $credittypes, $table = null, $pkey = null)
    {
        $r = parent::setCredits($users, $credittypes, 'schedule.show_credit', 'show_id');
        $this->updateCacheObject();

        return $r;
    }

    /**
     * Gets all podcasts linked to this show.
     *
     * @param bool $include_suspended Whether to include suspended podcasts in the result
     *
     * @return MyRadio_Podcast[]
     */
    public function getAllPodcasts($include_suspended = false)
    {
        $andSuspend = "";

        // This makes me sad, but it passes "false" from API,
        // which is true because it isn't "". ¯\_(ツ)_/¯
        if (!$include_suspended || $include_suspended == "false") {
            $andSuspend = " AND suspended = false";
        }

        $query = "SELECT podcast_id FROM schedule.show_podcast_link
        INNER JOIN uryplayer.podcast USING (podcast_id)
        WHERE show_id = $1"
        . $andSuspend
        . " ORDER BY submitted DESC";

        $ids = self::$db->fetchColumn(
            $query,
            [$this->getID()]
        );

        $podcasts = [];
        foreach ($ids as $id) {
            $podcasts[] = MyRadio_Podcast::getInstance($id);
        }

        return $podcasts;
    }

    /**
     * Returns all Shows of the given type. Caches for 1h.
     *
     * @return MyRadio_Show[]
     */
    public static function getAllShows($show_type_id = 1, $current_term_only = false)
    {
        $key = 'MyRadio_Show_AllShowsFetcher_last_'.$show_type_id.'_'.(int) $current_term_only;

        $keys = self::$cache->get($key);

        if ($keys) {
            $results = self::$cache->getAll($keys);
            $shows = array_values($results); // Cached results are in different format.
        } else {
            $sql = self::BASE_SHOW_SQL.' WHERE show_type_id=$1';
            $params = [$show_type_id];
            if ($current_term_only) {
                $sql .= ' AND EXISTS (
                            SELECT * FROM schedule.show_season
                            WHERE schedule.show_season.show_id=schedule.show.show_id
                            AND schedule.show_season.termid=$2
                        )';
                $params[] = MyRadio_Term::getActiveApplicationTerm()->getID();
            }

            $result = self::$db->fetchAll($sql, $params);

            $shows = [];
            $show_keys = [];
            foreach ($result as $row) {
                $show = new self($row);
                $show->updateCacheObject();
                $shows[] = $show;
                $show_keys[] = self::getCacheKey($show->getID());
            }

            self::$cache->set($key, $show_keys);
        }

        return $shows;
    }

    /**
     * Find the most messaged shows.
     *
     * @param int $date If specified, only messages for timeslots since $date are counted.
     *
     * @return array An array of 30 Shows that have been put through toDataSource, with the addition of a msg_count key,
     *               referring to the number of messages sent to that show.
     */
    public static function getMostMessaged($date = 0)
    {
        $result = self::$db->fetchAll(
            'SELECT show.show_id, COUNT(*) as msg_count FROM sis2.messages
            LEFT JOIN schedule.show_season_timeslot ON messages.timeslotid=show_season_timeslot.show_season_timeslot_id
            LEFT JOIN schedule.show_season ON show_season_timeslot.show_season_id=show_season.show_season_id
            LEFT JOIN schedule.show ON show_season.show_id=show.show_id
            WHERE show_season_timeslot.start_time > $1 GROUP BY show.show_id ORDER BY msg_count DESC LIMIT 30',
            [CoreUtils::getTimestamp($date)]
        );

        $top = [];
        foreach ($result as $r) {
            $show = self::getInstance($r['show_id'])->toDataSource();
            $show['msg_count'] = intval($r['msg_count']);
            $top[] = $show;
        }

        return $top;
    }

    /**
     * Returns the current Show on air, if there is one.
     *
     * @param int $time Optional integer timestamp
     *
     * @return MyRadio_Show|null
     */
    public static function getCurrentShow($time = null)
    {
        $timeslot = MyRadio_Timeslot::getCurrentTimeslot($time);
        if (empty($timeslot)) {
            return;
        } else {
            return $timeslot->getSeason()->getShow();
        }
    }

    /**
     * Find the most listened Shows.
     *
     * @param int $date If specified, only messages for timeslots since $date are counted.
     *
     * @return array An array of 30 Timeslots that have been put through toDataSource, with the addition of a msg_count
     *               key, referring to the number of messages sent to that show.
     */
    public static function getMostListened($date = 0)
    {
        $key = 'stats_show_mostlistened';
        if (($top = self::$cache->get($key)) !== false) {
            return $top;
        }

        $result = self::$db->fetchAll(
            'SELECT show_id, SUM(listeners) AS listeners_sum FROM (
                SELECT show_season_id, (
                    SELECT COUNT(*) FROM strm_log
                    WHERE (starttime < show_season_timeslot.start_time AND endtime >= show_season_timeslot.start_time)
                    OR (
                        starttime >= show_season_timeslot.start_time
                        AND starttime < show_season_timeslot.start_time + show_season_timeslot.duration
                    )
                ) AS listeners
                FROM schedule.show_season_timeslot
                WHERE start_time > $1
            ) AS t1
            LEFT JOIN schedule.show_season ON t1.show_season_id = show_season. show_season_id
            GROUP BY show_id ORDER BY listeners_sum DESC LIMIT 30',
            [CoreUtils::getTimestamp($date)]
        );

        $top = [];
        foreach ($result as $r) {
            $show = self::getInstance($r['show_id'])->toDataSource();
            $show['listeners'] = intval($r['listeners_sum']);
            $top[] = $show;
        }

        self::$cache->set($key, $top, 86400);

        return $top;
    }

    /**
     * Searches searchable *text* metadata for the specified value. Does not work for image metadata.
     * if $q is set, then $path_query must be set to "search". This allows the query to be given in path or in parameters.
     *
     * @todo effective_from/to not yet implemented
     *
     * @param string $path_query     The query value encoded in the path (DEPRECATED).
     * @param string $q              The query value as a query string. to use this, $path_query must be set to "search"
     * @param array  $string_keys    The metadata keys to search
     * @param int    $effective_from UTC Time to search from.
     * @param int    $effective_to   UTC Time to search to.
     *
     * @return array The shows that match the search terms
     */
    public static function searchMeta($path_query, $q="", $string_keys = null, $effective_from = null, $effective_to = null)
    {
        if($path_query != "search" && $q != "") {
            throw new MyRadioException(
                "the path_query must be set to 'search' if q is set in the query string",
                400
            );
        }
        if ($path_query == "search" && $q != ""){
            $query = $q;
        } else {
            $query = $path_query;
        }
        if (is_null($string_keys)) {
            $string_keys = ['title', 'description', 'tag'];
        }

        $r = parent::searchMetaBase(
            $query,
            $string_keys,
            $effective_from,
            $effective_to,
            'schedule.show_metadata',
            'show_id'
        );
        return self::resultSetToObjArray($r);
    }

    /**
     * Generate a podcast RSS feed for this show.
     * @return string
     */
    public function getPodcastRss()
    {
        $website = preg_replace(
            '(/$)',
            '',
            'https:' .Config::$website_url
        );
        $media_url = preg_replace(
            '(/$)',
            '',
            $website . '/' . Config::$public_media_uri
        );

        $writer = new \XMLWriter();
        $writer->openMemory();
        $writer->startDocument('1.0', 'UTF-8');
        $writer->setIndent(true);

        $writer->startElement('rss');
        $writer->writeAttribute('xmlns:itunes', 'http://www.itunes.com/dtds/podcast-1.0.dtd');
        $writer->writeAttribute('xmlns:spotify', 'https://www.spotify.com/ns/rss');
        $writer->writeAttribute('version', '2.0');

        $writer->startElement('channel');

        $writer->writeElement("title", $this->getMeta("title"));
        $writer->writeElement("link", $website . $this->getWebpage());

        $writer->startElement("description");
        $writer->writeCdata(
            str_replace(
                '&nbsp;',
                ' ',
                html_entity_decode(
                    strip_tags($this->getMeta("description"), ['a', 'p']),
                    ENT_QUOTES | ENT_XML1,
                    "UTF-8"
                )
            )
        );
        $writer->endElement();

        $writer->writeElement("language", "en"); // TODO

        $writer->writeElementNs("itunes", "author", null, Config::$long_name);

        $writer->startElementNs("itunes", "category", null);
        $writer->writeAttribute("text", "Society & Culture"); // TODO
        $writer->endElement();

        $writer->startElementNs("itunes", "image", null);
        $writer->writeAttribute(
            "href",
            $website . $this->getShowPhoto()
        );
        $writer->endElement();

        $writer->startElementNs("itunes", "owner", null);

        $writer->writeElementNs("itunes", "name", null, Config::$long_name);
        $writer->writeElementNs("itunes", "email", null, "pc@" . Config::$email_domain);

        $writer->endElement();

        $writer->writeElementNs(
            "itunes",
            "explicit",
            null,
            $this->isPodcastExplicit() ? "true" : "false"
        );
        
        $writer->writeElement("copyright", "Copyright " . date("Y") . " " . Config::$long_name . ". All rights reserved.");

        foreach ($this->getAllPodcasts() as $episode) {
            if (!($episode->isPublished())) {
                continue;
            }

            $fileSize = filesize($episode->getWebFile());

            if (empty($fileSize)) {
                // that file is in the twilight zone
                continue;
            }

            $writer->startElement("item");

            $writer->writeElement("guid", $episode->getGUID());
            $writer->writeElement("title", $episode->getMeta("title"));

            $writer->startElement("description");
            $writer->writeCdata(
                $episode->getMeta("description")
            );
            $writer->endElement();

            $writer->writeElement("pubDate", CoreUtils::getRfc2822Timestamp($episode->getSubmitted()));

            if (!empty($episode->getCover())) {
                $writer->startElementNs("itunes", "image", null);
                $writer->writeAttribute(
                    "href",
                    $media_url.$episode->getCover()
                );
                $writer->endElement();
            }

            $getID3 = new \getID3();
            $fileInfo = $getID3->analyze($episode->getWebFile());

            if (isset($fileInfo["playtime_string"])) {
                $writer->writeElementNs("itunes", "duration", null, $fileInfo['playtime_string']);
            }

            $writer->startElement("enclosure");
            $writer->writeAttribute("url", $website . $episode->getURI());
            $writer->writeAttribute("type", "audio/mpeg"); // TODO
            $writer->writeAttribute("length", $fileSize);
            $writer->endElement();

            $writer->endElement();
        }

        $writer->endElement();
        $writer->endElement();
        $writer->endDocument();

        return $writer->flush();
    }


    public function toDataSource($mixins = [])
    {
        $data = [
            'show_id' => $this->getID(),
            'title' => $this->getMeta('title'),
            'credits_string' => implode(', ', $this->getCreditsNames(false)),
            'credits' => array_map(
                function ($x) {
                    $x['User'] = $x['User']->toDataSource();
                    $x['type_name'] = $this->getCreditName($x['type']);

                    return $x;
                },
                $this->getCredits()
            ),
            'description' => $this->getMeta('description'),
            'show_type_id' => $this->show_type,
            'subtype' => array_merge($this->getSubtype()->toDataSource($mixins), [
                // I don't like using html here, but if I use text it adds an unnecessary and ugly <a> tag
                'display' => 'html',
                'html' => $this->getSubtype()->getName()
            ]),
            'seasons' => [
                'display' => 'text',
                'value' => $this->getNumberOfSeasons(),
                'title' => 'Click to see Seasons for this show',
                'url' => URLUtils::makeURL('Scheduler', 'listSeasons', ['showid' => $this->getID()]),
            ],
            'editlink' => [
                'display' => 'icon',
                'value' => 'pencil',
                'title' => 'Edit Show',
                'url' => URLUtils::makeURL('Scheduler', 'editShow', ['showid' => $this->getID()]),
            ],
            'applylink' => [
                'display' => 'icon',
                'value' => 'calendar',
                'title' => 'Apply for a new Season',
                'url' => URLUtils::makeURL('Scheduler', 'editSeason', ['showid' => $this->getID()]),
            ],
            'uploadlink' => [
                'display' => 'icon',
                'value' => 'upload',
                'title' => 'upload show art',
                'url' => URLUtils::makeURL('Scheduler', 'showPhoto', ['show_id' => $this->getID()]),
            ],
            'micrositelink' => [
                'display' => 'icon',
                'value' => 'link',
                'title' => 'View Show Microsite',
                'url' => $this->getWebpage(),
            ],
            'photo' => $this->getShowPhoto(),
        ];

        return $data;
    }
}
