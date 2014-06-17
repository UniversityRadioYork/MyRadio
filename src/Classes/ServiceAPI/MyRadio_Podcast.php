<?php

/**
 * Provides the MyRadio_APIKey class for MyRadio
 * @package MyRadio_Podcast
 */

/**
 * Podcasts. For the website.
 *
 * Reminder: Podcasts may not include any copyrighted content. This includes
 * all songs and *beds*.
 *
 * @version 20130815
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @package MyRadio_Podcast
 * @uses \Database
 */
class MyRadio_Podcast extends MyRadio_Metadata_Common
{
    /**
     * The Podcast's ID
     * @var int
     */
    private $podcast_id;

    /**
     * The path to the file, relative to Config::$public_media_uri
     * @var String
     */
    private $file;

    /**
     * The Time the Podcast was uploaded
     * @var int
     */
    private $submitted;

    /**
     * The ID of the User that uploaded the Podcast
     * @var int
     */
    private $memberid;

    /**
     * The ID of the User that approved the Podcast
     * @var int
     */
    private $approvedid;

    /**
     * Array of Users and their relation to the Podcast.
     * @var Array
     */
    protected $credits = [];

    /**
     * The ID of the show this is linked to, if any.
     * @var int
     */
    private $show_id;

    /**
     * Construct the API Key Object
     * @param String $key
     */
    protected function __construct($podcast_id)
    {
        $this->podcast_id = (int) $podcast_id;

        $result = self::$db->fetchOne(
            'SELECT file, memberid, approvedid, submitted, show_id, (
                SELECT array(
                    SELECT metadata_key_id FROM uryplayer.podcast_metadata
                    WHERE podcast_id=$1 AND effective_from <= NOW()
                    ORDER BY effective_from, podcast_metadata_id
                )
            ) AS metadata_types, (
                SELECT array(
                    SELECT metadata_value FROM uryplayer.podcast_metadata
                    WHERE podcast_id=$1 AND effective_from <= NOW()
                    ORDER BY effective_from, podcast_metadata_id
                )
            ) AS metadata, (
                SELECT array(
                    SELECT metadata_value FROM uryplayer.podcast_image_metadata
                    WHERE podcast_id=$1 AND effective_from <= NOW()
                    ORDER BY effective_from, podcast_image_metadata_id
                )
            ) AS image_metadata, (
                SELECT array(
                    SELECT credit_type_id FROM uryplayer.podcast_credit
                    WHERE podcast_id=$1 AND effective_from <= NOW()
                    AND (effective_to IS NULL OR effective_to >= NOW())
                    AND approvedid IS NOT NULL
                    ORDER BY podcast_credit_id
                )
            ) AS credit_types, (
                SELECT array(
                    SELECT creditid FROM uryplayer.podcast_credit
                    WHERE podcast_id=$1 AND effective_from <= NOW()
                    AND (effective_to IS NULL OR effective_to >= NOW())
                    AND approvedid IS NOT NULL
                    ORDER BY podcast_credit_id
                )
            ) AS credits
            FROM uryplayer.podcast
            LEFT JOIN schedule.show_podcast_link USING (podcast_id)
            WHERE podcast_id=$1',
            [$podcast_id]
        );

        if (empty($result)) {
            throw new MyRadioException('Podcast ' . $podcast_id, ' does not exist.', 404);
        }

        $this->file = $result['file'];
        $this->memberid = (int) $result['memberid'];
        $this->approvedid = (int) $result['approvedid'];
        $this->submitted = strtotime($result['submitted']);
        $this->show_id = (int) $result['show_id'];

        //Deal with the Credits arrays
        $credit_types = self::$db->decodeArray($result['credit_types']);
        $credits = self::$db->decodeArray($result['credits']);

        for ($i = 0; $i < sizeof($credits); $i++) {
            if (empty($credits[$i])) {
                continue;
            }
            $this->credits[] = [
                'type' => (int) $credit_types[$i],
                'memberid' => $credits[$i],
                'User' => MyRadio_User::getInstance($credits[$i])
            ];
        }


        //Deal with the Metadata arrays
        $metadata_types = self::$db->decodeArray($result['metadata_types']);
        $metadata = self::$db->decodeArray($result['metadata']);

        for ($i = 0; $i < sizeof($metadata); $i++) {
            if (self::isMetadataMultiple($metadata_types[$i])) {
                //Multiples should be an array
                $this->metadata[$metadata_types[$i]][] = $metadata[$i];
            } else {
                $this->metadata[$metadata_types[$i]] = $metadata[$i];
            }
        }
    }

    /**
     * Get all the Podcasts that the User is Owner of Creditor of.
     * @param MyRadio_User $user Default current user.
     * @return MyRadio_Podcast[]
     */
    public static function getPodcastsAttachedToUser(MyRadio_User $user = null)
    {
        return self::resultSetToObjArray(self::getPodcastIDsAttachedToUser($user));
    }

    /**
     * Get the IDs of all the Podcasts that the User is Owner of Creditor of.
     * @param MyRadio_User $user Default current user.
     * @return int[]
     */
    public static function getPodcastIDsAttachedToUser(MyRadio_User $user = null)
    {
        if ($user === null) {
            $user = MyRadio_User::getInstance();
        }

        return self::$db->fetchColumn(
            'SELECT podcast_id FROM uryplayer.podcast
            WHERE memberid=$1 OR podcast_id IN (
                SELECT podcast_id FROM uryplayer.podcast_credit
                WHERE creditid=$1 AND effective_from <= NOW()
                AND (effective_to >= NOW() OR effective_to IS NULL)
            )',
            [$user->getID()]
        );
    }

    public static function getPending()
    {
        self::initDB();

        return self::resultSetToObjArray(self::$db->fetchColumn(
            'SELECT podcast_id FROM uryplayer.podcast WHERE submitted IS NULL'
        ));
    }

    public static function getCreateForm()
    {
        $titleField = new MyRadioFormField(
            'title',
            MyRadioFormField::TYPE_TEXT,
            ['label' => 'Title']
        );

        $descField = new MyRadioFormField(
            'description',
            MyRadioFormField::TYPE_BLOCKTEXT,
            ['label' => 'Description']
        );

        $tagsField = new MyRadioFormField(
            'tags',
            MyRadioFormField::TYPE_TEXT,
            [
                'label' => 'Tags',
                'explanation' => 'A set of keywords to describe your podcast '
                . 'generally, seperated with spaces.'
            ]
        );

        $form = (new MyRadioForm(
            'createpodcastfrm',
            'Podcast',
            'createPodcast',
            ['title' => 'Create Podcast']
        )
        )->addField($titleField)->addField($descField)->addField($tagsField);

        //Get User's shows, or all shows if they have AUTH_PODCASTANYSHOW
        //Format them into a select field format.
        $auth = MyRadio_User::getInstance()->hasAuth(AUTH_PODCASTANYSHOW);
        $shows = array_map(
            function ($x) {
                return ['text' => $x->getMeta('title'), 'value' => $x->getID()];
            },
            $auth ? MyRadio_Show::getAllShows() : MyRadio_User::getInstance()->getShows()
        );

        //Add an option for not attached to a show
        if (MyRadio_User::getInstance()->hasAuth(AUTH_STANDALONEPODCAST)) {
            $shows = array_merge([['text' => 'Standalone']], $shows);
        }

        $form->addField(
            new MyRadioFormField(
                'show',
                MyRadioFormField::TYPE_SELECT,
                [
                    'options' => $shows,
                    'explanation' => 'This Podcast will be attached to the '
                    . 'Show you select here.',
                    'label' => 'Show',
                    'required' => false
                ]
            )
        )->addField(
            new MyRadioFormField(
                'credits',
                MyRadioFormField::TYPE_TABULARSET,
                [
                    'label' => 'Credits', 'options' => [
                        new MyRadioFormField(
                            'member',
                            MyRadioFormField::TYPE_MEMBER,
                            [
                                'explanation' => '',
                                'label' => 'Person'
                            ]
                        ),
                        new MyRadioFormField(
                            'credittype',
                            MyRadioFormField::TYPE_SELECT,
                            [
                                'options' => array_merge(
                                    [
                                        [
                                            'text' => 'Please select...',
                                            'disabled' => true
                                        ]
                                    ],
                                    MyRadio_Scheduler::getCreditTypes()
                                ),
                                'explanation' => '',
                                'label' => 'Role'
                            ]
                        )
                    ]
                ]
            )
        )->addField(
            new MyRadioFormField(
                'file',
                MyRadioFormField::TYPE_FILE,
                [
                    'label' => 'Audio',
                    'explanation' => 'Upload the original, high-quality audio for'
                    . ' this podcast. We\'ll publish a version optimised for the web'
                    . ' and archive the original. Max size 500MB.',
                    'options' => ['progress' => true]
                ]
            )
        )->addField(
            new MyRadioFormField(
                'terms',
                MyRadioFormField::TYPE_CHECK,
                [
                    'label' => 'I have read and confirm that this audio file complies'
                    . ' with <a href="/wiki/Podcasting_Policy" target="_blank">'
                    . Config::$short_name . '\'s Podcasting Policy</a>.'
                ]
            )
        );

        return $form;
    }

    /**
     * Create a new Podcast
     * @param String $title The Podcast's title
     * @param String $description The Podcast's description
     * @param Array $tags An array of String tags
     * @param String $file The local filesystem path to the Podcast file
     * @param MyRadio_Show $show The show to attach the Podcast to
     * @param Array $credits Credit data. Format compatible with a credit
     * TABULARSET (see Scheduler)
     */
    public static function create(
        $title,
        $description,
        $tags,
        $file,
        MyRadio_Show $show = null,
        $credits = null
    ) {

        //Get an ID for the new Podcast
        $id = (int) self::$db->fetchColumn(
            'INSERT INTO uryplayer.podcast '
            . '(memberid, approvedid, submitted) VALUES ($1, $1, NULL) '
            . 'RETURNING podcast_id',
            [MyRadio_User::getInstance()->getID()]
        )[0];

        $podcast = self::getInstance($id);

        $podcast->setMeta('title', $title);
        $podcast->setMeta('description', $description);
        $podcast->setMeta('tag', $tags);
        $podcast->setCredits($credits['member'], $credits['credittype']);
        if (!empty($show)) {
            $podcast->setShow($show);
        }

        //Ship the file off to the archive location to be converted
        if (!move_uploaded_file($file, $podcast->getArchiveFile())) {
            throw new MyRadioException(
                "Failed to move podcast file "
                . "$file to {$podcast->getArchiveFile()}",
                500
            );
        }
    }

    /**
     * Get the Podcast ID
     * @return int
     */
    public function getID()
    {
        return $this->podcast_id;
    }

    /**
     * Get the Show this Podcast is linked to, if there is one.
     * @return MyRadio_Show
     */
    public function getShow()
    {
        if (!empty($this->show_id)) {
            return MyRadio_Show::getInstance($this->show_id);
        } else {
            return null;
        }
    }

    /**
     * Returns a human-readable explanation of the Podcast's state.
     * @return String
     */
    public function getStatus()
    {
        if (empty($this->submitted)) {
            return 'Processing...';
        } elseif ($this->submitted > time()) {
            return 'Scheduled for publication ('.CoreUtils::happyTime($this->submitted).')';
        } else {
            return 'Published';
        }
    }

    /**
     * Get the file system path to where the original file is stored.
     * @return String
     */
    public function getArchiveFile()
    {
        return Config::$podcast_archive_path.'/'.$this->getID().'.orig';
    }

    /**
     * Get the file system path to where the web file should be stored
     * @return String
     */
    public function getWebFile()
    {
        return Config::$public_media_path.'/podcasts/MyRadioPodcast'.$this->getID().'.mp3';
    }

    /**
     * Get the value that *should* be stored in uryplayer.podcast.file
     * @return String
     */
    public function getFile()
    {
        return 'podcasts/MyRadioPodcast'.$this->getID().'.mp3';
    }

    /**
     * Set the Show this Podcast is linked to. If null, removes any link.
     * @param MyRadio_Show $show
     */
    public function setShow(MyRadio_Show $show)
    {
        self::$db->query(
            'DELETE FROM schedule.show_podcast_link
            WHERE podcast_id=$1',
            [$this->getID()]
        );

        if (!empty($show)) {
            self::$db->query(
                'INSERT INTO schedule.show_podcast_link
                (show_id, podcast_id) VALUES ($1, $2)',
                [$show->getID(), $this->getID()]
            );
            $this->show_id = $show->getID();
        } else {
            $this->show_id = null;
        }
    }

    /**
     * Get data in array format
     * @param boolean $full If true, returns more data.
     * @return Array
     */
    public function toDataSource($full = true)
    {
        $data = [
            'podcast_id' => $this->getID(),
            'title' => $this->getMeta('title'),
            'description' => $this->getMeta('description'),
            'status' => $this->getStatus(),
            'editlink' => [
                'display' => 'icon',
                'value' => 'script',
                'title' => 'Edit Podcast',
                'url' => CoreUtils::makeURL('Podcast', 'editPodcast', ['podcastid' => $this->getID()])
            ],
            'setcoverlink' => [
                'display' => 'icon',
                'value' => 'script',
                'title' => 'Set Cover',
                'url' => CoreUtils::makeURL('Podcast', 'setCover', ['podcastid' => $this->getID()])
            ]
        ];

        if ($full) {
            $data['credits'] = implode(', ', $this->getCreditsNames(false));
            $data['show'] = $this->getShow() ?
                $this->getShow()->toDataSource(false) : null;
        }

        return $data;
    }

    /**
     * Sets the current podcast cover for this podcast.
     *
     * @param string $url  The URL of the incoming podcast cover.
     */
    public function setCover($url)
    {
        // TODO: Plumb this into the metadata system.
        //       At time of writing, MyRadio's metadata system doesn't do images.
        if (empty($url)) {
            throw new MyRadioException('URL is blank.');
        }

        self::$db->query(
            'INSERT INTO uryplayer.podcast_image_metadata
            (metadata_key_id, podcast_id, memberid, approvedid,
            metadata_value, effective_from, effective_to)
            VALUES
            (10, $1, $2, $2, $3, NOW(), NULL),
            (11, $1, $2, $2, $3, NOW(), NULL)',
            [
                $this->getID(),
                MyRadio_User::getInstance()->getID(),
                $url
            ]
        );
    }

    /**
     * Gets the current podcast cover for this podcast.
     *
     * @return string  The URL of the current podcast cover.
     */
    public function getCover()
    {
        // TODO: Plumb this into the metadata system.
        //       At time of writing, MyRadio's metadata system doesn't do images.
        return self::$db->fetchOne(
            'SELECT metadata_value AS url
            FROM uryplayer.podcast_image_metadata
            WHERE podcast_id = $1
            AND effective_from <= NOW()
            AND (effective_to IS NULL OR effective_to > NOW())
            ORDER BY effective_from DESC
            LIMIT 1;',
            [$this->getID()]
        )['url'];
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
     * @param String $string_key The metadata key
     * @param mixed $value The metadata value. If key is_multiple and value is an array, will create instance
     * for value in the array.
     * @param int $effective_from UTC Time the metavalue is effective from. Default now.
     * @param int $effective_to UTC Time the metadata value is effective to. Default NULL (does not expire).
     * @param null $table Used for compatibility with parent.
     * @param null $pkey Used for compatibility with parent.
     */
    public function setMeta($string_key, $value, $effective_from = null, $effective_to = null, $table = null, $pkey = null)
    {
        parent::setMeta($string_key, $value, $effective_from, $effective_to, 'uryplayer.podcast_metadata', 'podcast_id');
    }

    /**
     * Updates the list of Credits.
     *
     * Existing credits are kept active, ones that are not in the new list are set to effective_to now,
     * and ones that are in the new list but not exist are created with effective_from now.
     *
     * @param MyRadio_User[] $users An array of Users associated.
     * @param int[] $credittypes The relevant credittypeid for each User.
     */
    public function setCredits($users, $credittypes, $table = null, $pkey = null)
    {
        parent::setCredits($users, $credittypes, 'uryplayer.podcast_credit', 'podcast_id');
    }

    /**
     * Set the time that the Podcast is submitted as visible on the website.
     * @param int $time
     */
    public function setSubmitted($time)
    {
        $this->submitted = $time;
        self::$db->query(
            'UPDATE uryplayer.podcast SET submitted=$1
            WHERE podcast_id=$2',
            [CoreUtils::getTimestamp($time), $this->getID()]
        );
    }

    /**
     * Convert the Archive file to the Web format.
     *
     * If the preferred format is changed, re-run this on every Podcast to
     * reencode them.
     */
    public function convert()
    {
        $tmpfile = $this->getArchiveFile();
        $dbfile = $this->getWebFile();
        shell_exec("nice -n 15 ffmpeg -i '$tmpfile' -ab 192k -f mp3 - >'{$dbfile}'");

        self::$db->query(
            'UPDATE uryplayer.podcast SET file=$1 WHERE podcast_id=$2',
            [$this->getFile(), $this->getID()]
        );
        if (empty($this->submitted)) {
            $this->setSubmitted(time());
        }
    }
}
