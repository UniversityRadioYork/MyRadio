<?php

/**
 * Provides the Season class for MyRadio.
 */
namespace MyRadio\ServiceAPI;

use MyRadio\Config;
use MyRadio\MyRadioException;
use MyRadio\MyRadio\CoreUtils;
use MyRadio\MyRadio\URLUtils;
use MyRadio\MyRadio\MyRadioForm;
use MyRadio\MyRadio\MyRadioFormField;
use MyRadio\MyRadioEmail;

/**
 * The Season class is used to create, view and manipulate Seasons within the new MyRadio Scheduler Format.
 *
 * @uses \Database
 * @uses \MyRadio_Show
 */
class MyRadio_Season extends MyRadio_Metadata_Common
{
    private $season_id;
    private $show_id;
    private $term_id;
    private $submitted;
    private $timeslots;
    private $requested_times = [];
    private $requested_weeks = [];
    private $season_num;
    private $subtype_id;
    protected $owner;

    protected function __construct($season_id)
    {
        $this->season_id = (int) $season_id;
        //Init Database
        self::initDB();

        //Get the basic info about the season
        $result = self::$db->fetchOne(
            'SELECT show_id, termid, submitted, memberid, (
                SELECT array_to_json(array(
                    SELECT metadata_key_id FROM schedule.season_metadata
                    WHERE show_season_id=$1 AND effective_from <= NOW()
                    AND (effective_to IS NULL OR effective_to >= NOW())
                    ORDER BY effective_from, season_metadata_id
                ))
            ) AS metadata_types, (
                SELECT array_to_json(array(
                    SELECT metadata_value FROM schedule.season_metadata
                    WHERE show_season_id=$1 AND effective_from <= NOW()
                    AND (effective_to IS NULL OR effective_to >= NOW())
                    ORDER BY effective_from, season_metadata_id
                ))
            ) AS metadata, (
                SELECT array_to_json(array(
                    SELECT requested_day FROM schedule.show_season_requested_time
                    WHERE show_season_id=$1 ORDER BY preference ASC
                ))
            ) AS requested_days, (
                SELECT array_to_json(array(
                    SELECT start_time FROM schedule.show_season_requested_time
                    WHERE show_season_id=$1
                    ORDER BY preference ASC
                ))
            ) AS requested_start_times, (
                SELECT array_to_json(array(
                    SELECT duration FROM schedule.show_season_requested_time
                    WHERE show_season_id=$1
                    ORDER BY preference ASC
                ))
            ) AS requested_durations, (
                SELECT array_to_json(array(
                    SELECT show_season_timeslot_id FROM schedule.show_season_timeslot
                    WHERE show_season_id=$1
                    ORDER BY start_time ASC
                ))
            ) AS timeslots, (
                SELECT array_to_json(array(
                    SELECT week FROM schedule.show_season_requested_week WHERE show_season_id=$1
                ))
            ) AS requested_weeks, (
                SELECT COUNT(*) FROM schedule.show_season
                WHERE show_id=(SELECT show_id FROM schedule.show_season WHERE show_season_id=$1)
                AND show_season_id<=$1
                AND show_season_id IN (SELECT show_season_id FROM schedule.show_season_timeslot)
            ) AS season_num, (
                SELECT show_subtype_id
                FROM schedule.show_season_subtype
                WHERE season_id=$1 OR show_id = show_season.show_id
                AND effective_from <= NOW()
                AND (effective_to IS NULL OR effective_to >= NOW())
                GROUP BY show_season_subtype_id
                ORDER BY effective_from, season_id, show_id
                LIMIT 1
            ) AS subtype_id
            FROM schedule.show_season WHERE show_season_id=$1',
            [$season_id]
        );
        if (empty($result)) {
            //Invalid Season
            throw new MyRadioException('The MyRadio_Season with instance ID #'.$season_id.' does not exist.');
        }

        //Deal with the easy bits
        $this->owner = MyRadio_User::getInstance($result['memberid']);
        $this->show_id = (int) $result['show_id'];
        $this->submitted = strtotime($result['submitted']);
        $this->term_id = (int) $result['termid'];
        $this->season_num = (int) $result['season_num'];
        $this->subtype_id = (int) $result['subtype_id'];

        $metadata_types = json_decode($result['metadata_types']);
        $metadata = json_decode($result['metadata']);
        //Deal with the metadata
        for ($i = 0; $i < sizeof($metadata_types); ++$i) {
            if (self::isMetadataMultiple($metadata_types[$i])) {
                $this->metadata[$metadata_types[$i]][] = $metadata[$i];
            } else {
                $this->metadata[$metadata_types[$i]] = $metadata[$i];
            }
        }

        //Requested Weeks
        $requested_weeks = json_decode($result['requested_weeks']);
        $this->requested_weeks = [];
        foreach ($requested_weeks as $requested_week) {
            $this->requested_weeks[] = intval($requested_week);
        }

        //Requested timeslots
        $requested_days = json_decode($result['requested_days']);
        $requested_start_times = json_decode($result['requested_start_times']);
        $requested_durations = json_decode($result['requested_durations']);

        for ($i = 0; $i < sizeof($requested_days); ++$i) {
            $this->requested_times[] = [
                'day' => (int) $requested_days[$i],
                'start_time' => (int) $requested_start_times[$i],
                'duration' => self::$db->intervalToTime($requested_durations[$i]),
            ];
        }

        $this->timeslots = json_decode($result['timeslots']);
    }

    /**
     * Creates a new MyRadio Season Application and returns an object representing it.
     *
     * @param array $params An array of Seasons properties compatible with the Models/Scheduler/seasonfrm Form, with a
     *                      few additional potential customisation options:
     *                      weeks: An Array of weeks, keyed wk1-10, representing the requested week<br>
     *                      times: a 2D Array of:<br>
     *                      day: An Array of one or more requested days, 0 being Monday, 6 being Sunday. Corresponds to
     *                      (s|e)time<br>
     *                      stime: An Array of sizeof(day) times, represeting the time of day the show should start<br>
     *                      etime: An Array of sizeof(day) times, represeting the time of day the show should end<br>
     *                      description: A description of this Season of the Show, in addition to the Show
     *                      description<br>
     *                      tags: A string of 0 or more space-seperated tags this Season relates to, in addition to the
     *                      Show tags<br>
     *                      show_id: The ID of the Show to assign the application to
     *                      termid: The ID of the term being applied for. Defaults to the current Term
     *
     * weeks, day, stime, etime, show_id are all required fields
     *
     * As this is the initial creation, all tags are <i>approved</i> by the submitter
     * so the Season has some initial values
     *
     * @throws MyRadioException
     */
    public static function create($params = [])
    {
        //Validate input
        $required = ['show_id', 'weeks', 'times'];
        foreach ($required as $field) {
            if (!isset($params[$field])) {
                throw new MyRadioException('Parameter '.$field.' was not provided.', 400);
            }
        }
        $tags = (!empty($params['tags'])) ? CoreUtils::explodeTags($params['tags']) : [];

        /**
         * Select an appropriate value for $term_id.
         */
        $term_id = MyRadio_Scheduler::getActiveApplicationTerm();

        //Start a transaction
        self::$db->query('BEGIN');

        //Right, let's start by getting a Season ID created for this entry
        $season_create_result = self::$db->fetchColumn(
            'INSERT INTO schedule.show_season
            (show_id, termid, submitted, memberid)
            VALUES ($1, $2, $3, $4) RETURNING show_season_id',
            [
                $params['show_id'],
                $term_id,
                CoreUtils::getTimestamp(),
                MyRadio_User::getInstance()->getID(),
            ],
            true
        );

        $season_id = $season_create_result[0];

        //Now let's allocate store the requested weeks for a term
        $any_weeks = false;
        for ($i = 1; $i <= 10; ++$i) {
            if ($params['weeks']["wk$i"]) {
                self::$db->query(
                    'INSERT INTO schedule.show_season_requested_week (show_season_id, week) VALUES ($1, $2)',
                    [$season_id, $i],
                    true
                );
                $any_weeks = true;
            }
        }
        if (!$any_weeks) {
            self::$db->query('ROLLBACK');
            throw new MyRadioException('A Season must at least have one requested week.', 400);
        }

        //Now for requested times
        for ($i = 0; $i < sizeof($params['times']['day']); ++$i) {
            $stime = $params['times']['stime'][$i];
            $etime = $params['times']['etime'][$i];
            if (is_null($params['times']['day'][$i]) || is_null($stime) || is_null($etime)) {
                throw new MyRadioException('Each requested time must have a day, start time and end time.', 400);
            }
            //Deal with the possibility of a show from 11pm to midnight etc.
            if ($stime < $etime) {
                $interval = CoreUtils::makeInterval($stime, $etime);
            } else {
                $interval = CoreUtils::makeInterval($stime, $etime + 86400);
            }

            //Enter the data
            self::$db->query(
                'INSERT INTO schedule.show_season_requested_time
                (requested_day, start_time, preference, duration, show_season_id)
                VALUES ($1, $2, $3, $4, $5)',
                [
                    $params['times']['day'][$i],
                    $stime,
                    $i,
                    $interval,
                    $season_id,
                ]
            );
        }

        //If the description metadata is non-blank, then update that too
        if (!empty($params['description'])) {
            self::$db->query(
                'INSERT INTO schedule.season_metadata
                (metadata_key_id, show_season_id, metadata_value, effective_from, memberid, approvedid)
                VALUES ($1, $2, $3, NOW(), $4, $4)',
                [
                    self::getMetadataKey('description'),
                    $season_id,
                    $params['description'],
                    MyRadio_User::getInstance()->getID(),
                ]
            );
        }

        //Same with tags
        foreach ($tags as $tag) {
            self::$db->query(
                'INSERT INTO schedule.season_metadata
                (metadata_key_id, show_season_id, metadata_value, effective_from, memberid, approvedid)
                VALUES ($1, $2, $3, NOW(), $4, $4)',
                [
                    self::getMetadataKey('tag'),
                    $season_id,
                    $tag,
                    MyRadio_User::getInstance()->getID(),
                ]
            );
        }

        // If the subtype isn't blank, add that in too
        if (!empty($params['subtype'])) {
            self::$db->query(
                'INSERT INTO schedule.show_season_subtype
                (season_id, show_subtype_id, effective_from)
                VALUES ($1, (
                    SELECT show_subtype_id FROM schedule.show_subtypes WHERE show_subtypes.class = $2
                    ), NOW())',
                [
                    $season_id,
                    $params['subtype']
                ]
            );
        }

        //Actually commit the show to the database!
        self::$db->query('COMMIT');

        MyRadio_Show::getInstance($params['show_id'])->addSeason($season_id);

        return self::getInstance($season_id);
    }

    public static function getForm()
    {
        //Set up the weeks checkboxes
        $weeks = [];
        for ($i = 1; $i <= 10; ++$i) {
            $weeks[] = new MyRadioFormField(
                'wk'.$i,
                MyRadioFormField::TYPE_CHECK,
                ['label' => 'Week '.$i, 'required' => false]
            );
        }

        return (
            new MyRadioForm(
                'sched_season',
                'Scheduler',
                'editSeason',
                [
                    'debug' => true,
                    'title' => 'Scheduler',
                    'subtitle' => 'New Season',
                ]
            )
        )->addField(
            new MyRadioFormField('show_id', MyRadioFormField::TYPE_HIDDEN)
        )->addField(
            new MyRadioFormField(
                'grp-basics',
                MyRadioFormField::TYPE_SECTION,
                ['label' => '']
            )
        )->addField(
            new MyRadioFormField(
                'weeks',
                MyRadioFormField::TYPE_CHECKGRP,
                [
                    'options' => $weeks,
                    'explanation' => 'Select what weeks this term this show will be on air',
                    'label' => 'Schedule for Weeks',
                ]
            )
        )->addField(
            new MyRadioFormField(
                'times',
                MyRadioFormField::TYPE_TABULARSET,
                [
                    'label' => 'Preferred Times',
                    'options' => [
                        new MyRadioFormField(
                            'day',
                            MyRadioFormField::TYPE_DAY,
                            ['label' => 'On']
                        ),
                        new MyRadioFormField(
                            'stime',
                            MyRadioFormField::TYPE_TIME,
                            ['label' => 'from']
                        ),
                        new MyRadioFormField(
                            'etime',
                            MyRadioFormField::TYPE_TIME,
                            ['label' => 'until']
                        ),
                    ],
                ]
            )
        )->addField(
            new MyRadioFormField(
                'grp-basics_close',
                MyRadioFormField::TYPE_SECTION_CLOSE
            )
        )->addField(
            new MyRadioFormField(
                'grp-adv',
                MyRadioFormField::TYPE_SECTION,
                ['label' => 'Advanced Options']
            )
        )->addField(
            new MyRadioFormField(
                'description',
                MyRadioFormField::TYPE_BLOCKTEXT,
                [
                    'explanation' => 'Each season of your show can have its own description. '
                        .'If you leave this blank, the main description for your Show will be used.',
                    'label' => 'Description',
                    'options' => ['minlength' => 140],
                    'required' => false,
                ]
            )
        )->addField(
            new MyRadioFormField(
                'tags',
                MyRadioFormField::TYPE_TEXT,
                [
                    'label' => 'Tags',
                    'explanation' => 'A set of keywords to describe this Season, separated by commas. '
                        .'These will be added onto the tags you already have set for the Show.',
                    'required' => false,
                ]
            )
        )->addField(
            new MyRadioFormField(
                'subtype',
                MyRadioFormField::TYPE_SELECT,
                [
                        'options' => array_merge(
                            [
                            ['value' => '', 'text' => 'Leave unchanged']
                            ],
                            MyRadio_ShowSubtype::getOptions()
                        ),
                        'label' => 'Subtype',
                        'explanation' => 'If necessary, override the subtype for this season. '
                            .'If you\'re not sure what you\'re doing, leave it as it is.',
                        'required' => false
                    ]
            )
        )->addField(
            new MyRadioFormField(
                'grp-adv_close',
                MyRadioFormField::TYPE_SECTION_CLOSE
            )
        );
    }

    public function getEditForm()
    {
        $showSubtype = $this->getShow()->getSubtype()->getClass();
        $seasonSubtype = $this->getSubtype()->getClass();
        return self::getForm()
            ->setSubTitle('Edit Season')
            ->editMode(
                $this->getID(),
                [
                    'show_id' => $this->show_id,
                    'description' => $this->getMeta('description'),
                    'tags' => implode(', ', $this->getMeta('tag')),
                    'subtype' => $showSubtype === $seasonSubtype ? '' : $seasonSubtype
                ]
            );
    }

    public function getAllocateForm()
    {
        $form = (
            new MyRadioForm(
                'sched_allocate',
                'Scheduler',
                'allocate',
                [
                    'title' => 'Scheduler',
                    'subtitle' => 'Allocate Timeslots to Season',
                    'template' => 'Scheduler/allocate.twig',
                ]
            )
        )->addField(
            new MyRadioFormField(
                'season_id', // NOTE: Needed by this name for passing the season ID around for allocation
                MyRadioFormField::TYPE_HIDDEN,
                ['value' => $this->getID()]
            )
        );

        //Set up the weeks checkboxes
        $weeks = [];
        for ($i = 1; $i <= 10; ++$i) {
            $weeks[] = new MyRadioFormField(
                'wk'.$i,
                MyRadioFormField::TYPE_CHECK,
                [
                    'label' => 'Week '.$i,
                    'required' => false,
                    'options' => ['checked' => in_array($i, $this->getRequestedWeeks())],
                ]
            );
        }

        //Set up the requested times radios
        $times = [];
        $i = 0;
        foreach ($this->getRequestedTimesAvail() as $time) {
            $times[] = [
                'value' => $i,
                'text' => empty($time['info']) ? $time['time'] : $time['time'].' - '.$time['info'],
                'disabled' => $time['conflict'],
                'class' => $time['conflict'] ? 'alert alert-danger' : '',
            ];
            ++$i;
        }

        $times[] = ['value' => -1, 'text' => 'Other (Choose below)'];

        $form->addField(
            new MyRadioFormField(
                'weeks',
                MyRadioFormField::TYPE_CHECKGRP,
                [
                    'options' => $weeks,
                    'label' => 'Schedule for Weeks',
                ]
            )
        )->addField(
            new MyRadioFormField(
                'time',
                MyRadioFormField::TYPE_RADIO,
                [
                    'options' => $times,
                    'label' => 'Timeslot',
                    'required' => false,
                ]
            )
        )->addField(
            new MyRadioFormField(
                'timecustom_day',
                MyRadioFormField::TYPE_DAY,
                [
                    'label' => 'Other Day: ',
                    'required' => false,
                ]
            )
        )->addField(
            new MyRadioFormField(
                'timecustom_stime',
                MyRadioFormField::TYPE_TIME,
                [
                    'label' => 'from',
                    'required' => false,
                ]
            )
        )->addField(
            new MyRadioFormField(
                'timecustom_etime',
                MyRadioFormField::TYPE_TIME,
                [
                    'label' => 'duration',
                    'required' => false,
                    'value' => '01:00',
                ]
            )
        );

        return $form;
    }

    public static function getRejectForm()
    {
        return (
            new MyRadioForm(
                'sched_reject',
                'Scheduler',
                'reject',
                [
                    'debug' => false,
                    'title' => 'Scheduler',
                    'subtitle' => 'Reject Season Application'
                ]
            )
        )->addField(
            new MyRadioFormField('season_id', MyRadioFormField::TYPE_HIDDEN)
        )->addField(
            new MyRadioFormField(
                'reason',
                MyRadioFormField::TYPE_BLOCKTEXT,
                [
                    'label' => 'Reason for Rejection: ',
                    'explanation' => 'You can enter a reason here for the application being rejected.'
                        .' If you then choose to send this response to the applicant, they can then edit their'
                        .' application and resubmit.',
                ]
            )
        )->addField(
            new MyRadioFormField(
                'notify_user',
                MyRadioFormField::TYPE_CHECK,
                [
                    'label' => 'Notify the Applicant via Email?',
                    'options' => ['checked' => true],
                    'required' => false,
                ]
            )
        );
    }

    public function setCredits($users, $credittypes, $table = null, $pkey = null)
    {
        // We don't have season credits, just show credits at the appropriate times.
        $r = parent::setCredits($users, $credittypes, 'schedule.show_credit', 'show_id');
        $this->updateCacheObject();

        return $r;
    }

    /**
     * Get a list of all Seasons that were for the current term, or
     * if we are not currently in a Term, the most recenly finished term.
     *
     * @return MyRadio_Season[]
     */
    public static function getAllSeasonsInLatestTerm()
    {
        $result = self::$db->fetchColumn(
            'SELECT termid FROM public.terms
            WHERE start <= NOW() ORDER BY finish DESC LIMIT 1'
        );

        if (empty($result)) {
            return [];
        }

        return self::getAllSeasonsInTerm($result[0]);
    }

    /**
     * Get all the Seasons in the active term.
     *
     * @param int $term_id
     *
     * @return MyRadio_Season[]
     */
    public static function getAllSeasonsInTerm($term_id)
    {
        return self::resultSetToObjArray(
            self::$db->fetchColumn(
                'SELECT show_season_id FROM schedule.show_season WHERE termid=$1',
                [$term_id]
            )
        );
    }

    /**
     * Rejects the application for the Season, notifying the creditors if asked.
     *
     * Will not reject if already rejected.<br>
     * A Season is "Rejected" by setting the "Submitted" field in schedule.show_season to NULL,
     * and adding a "reject-reason" metadata key, with the effective_from set to the time the application
     * was rejected.<br>
     * A Season can be reapplied for by setting the "Submitted" field to the re-submit time.
     * It is also best practice to then set the "reject-reason" key to have the same effective_to.
     *
     * @param string $reason      Why the application was rejected
     * @param bool   $notify_user If true, all creditors will be notified about the rejection.
     */
    public function reject($reason, $notify_user = true)
    {
        if ($this->submitted == null) {
            return false;
        }
        self::$db->query('BEGIN');
        self::$db->query('UPDATE schedule.show_season SET submitted=NULL WHERE show_season_id=$1', [$this->getID()]);
        $this->submitted = null;

        $this->setMeta('reject-reason', $reason);

        if ($notify_user) {
            $sname = Config::$short_name; // Heredocs can't do static class variables
            $email = Config::$email_domain;
            MyRadioEmail::sendEmailToUserSet(
                $this->getShow()->getCreditObjects(),
                $this->getMeta('title').' Application Rejected',
                <<<EOT
Hi #NAME,

Your application for a season of a show was rejected by our programming team, for the reason given below:

$reason

You can reapply online at any time, or for more information, email pc@{$email}.


~ {$sname} Scheduling Legume
EOT
            );
        }

        self::$db->query('COMMIT');
    }

    public function getMeta($meta_string)
    {
        $key = self::getMetadataKey($meta_string);
        if (isset($this->metadata[$key])) {
            return $this->metadata[$key];
        } else {
            return $this->getShow()->getMeta($meta_string);
        }
    }

    /**
     * Alias for getCredits($this->getShow()), which enables credits to be
     * automatically inherited from the show.
     * @param parent Unused for type compatibility with parent
     * @return array[]
     */
    public function getCredits(\MyRadio\ServiceAPI\MyRadio_Metadata_Common $parent = null)
    {
        return parent::getCredits($this->getShow());
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
            'schedule.season_metadata',
            'show_season_id'
        );
        $this->metadata[$string_key] = $value;
        $this->updateCacheObject();

        return $r;
    }

    public function getID()
    {
        return $this->season_id;
    }

    /**
     * @return MyRadio_Show
     */
    public function getShow()
    {
        return MyRadio_Show::getInstance($this->show_id);
    }

    public function getSubmittedTime()
    {
        return CoreUtils::happyTime($this->submitted);
    }

    /**
     * Get the microsite URI.
     *
     * @return string
     */
    public function getWebpage()
    {
        return '/schedule/shows/seasons/'.$this->getID();
    }

    /**
     * Gets the subtype for this season.
     * @return MyRadio_ShowSubtype
     */
    public function getSubtype()
    {
        if ($this->subtype_id === null) {
            return $this->getShow()->getSubtype();
        }
        return MyRadio_ShowSubtype::getInstance($this->subtype_id);
    }

    /**
     * Sets this season's subtype.
     *
     * @todo support effectiveFrom and effectiveTo
     * @param $subtypeId
     */
    public function setSubtype($subtypeId)
    {
        self::$db->query('UPDATE schedule.show_season_subtype SET show_subtype_id = $1 WHERE season_id = $1', [
            $subtypeId, $this->season_id
        ]);
    }

    /**
     * Sets this season's subtype by the subtype name.
     * @param $subtypeName
     */
    public function setSubtypeByName($subtypeName)
    {
        self::$db->query(
            'UPDATE schedule.show_season_subtype
            SET show_subtype_id = subtype.show_subtype_id
            FROM (SELECT show_subtype_id FROM schedule.show_subtypes WHERE show_subtypes.class = $2) AS subtype
            WHERE season_id = $1',
            [$this->season_id, $subtypeName]
        );
    }

    /**
     * Clears the subtype for this season, resetting it to the show's "main" subtype.
     */
    public function clearSubtype()
    {
        self::$db->query('DELETE FROM schedule.show_season_subtype WHERE season_id = $1', [$this->season_id]);
    }

    public function getRequestedTimes()
    {
        $return = [];
        foreach ($this->requested_times as $time) {
            $return[] = $this->formatTimeHuman($time);
        }

        return $return;
    }

    private function formatTimeHuman($time)
    {
        $stime = gmdate(' H:i', $time['start_time']);
        $etime = gmdate('H:i', $time['start_time'] + $time['duration']);

        return self::getDayNameFromID($time['day']).$stime.' - '.$etime;
    }

    private function getDayNameFromID($dow)
    {
        $days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

        return $days[$dow];
    }

    /**
     * Fetches requested times for the season and checks for conflicts.
     *
     * Returns a 2D array:
     * time: Value as per getRequestedTimes()
     * conflict: True if one or more of requested weeks already have a booking that time
     * info: If True above, will have human-readable why-it-is-a-conflict details. It may also contain information about
     *       "warnings" - conflicts on weeks this show isn't planned to be aired
     *
     * @return array time, conflict, info
     */
    public function getRequestedTimesAvail()
    {
        $return = [];
        foreach ($this->requested_times as $time) {
            //Check for existence of shows in requested times
            $conflicts = MyRadio_Scheduler::getScheduleConflicts($this->term_id, $time);

            if (!empty($conflicts)) {
                $conflict = '';
                $warning = '';

                foreach ($conflicts as $wk => $season_id) {
                    // Check if week is requested
                    if (in_array($wk, $this->requested_weeks)) {
                        $conflict .= self::getInstance($season_id)->getMeta('title').' (week '.$wk.'). ';
                    } else {
                        $warning .= self::getInstance($season_id)->getMeta('title').' (week '.$wk.'). ';
                    }
                }

                if (!empty($conflict)) {
                    // return conflicts
                    $return[] = [
                        'time' => self::formatTimeHuman($time),
                        'conflict' => true,
                        'info' => 'Conflicts with: ' . $conflict
                    ];
                } else {
                    // return warning
                    $return[] = [
                        'time' => self::formatTimeHuman($time),
                        'conflict' => false,
                        'info' => 'Warnings with: ' . $warning
                    ];
                }
            } else {
                // no conflicts or warnings
                $return[] = ['time' => self::formatTimeHuman($time), 'conflict' => false, 'info' => ''];
            }
        }

        return $return;
    }

    public function getRequestedWeeks()
    {
        return $this->requested_weeks;
    }

    /**
     * Get the Season number - for the first season of a show, this is 1, for the second it's 2 etc.
     * Seasons that don't have any timeslots scheduled do not count toward this value.
     *
     * @return int
     */
    public function getSeasonNumber()
    {
        return $this->season_num;
    }

    /**
     * Serialises the season. Merges with the parent show object as well.
     */
    public function toDataSource($mixins = [])
    {
        $first_time = $this->getFirstTime();
        $requested_times = $this->getRequestedTimes();

        return array_merge(
            $this->getShow()->toDataSource($mixins),
            [
                'season_id' => $this->getID(),
                'season_num' => $this->getSeasonNumber(),
                'title' => $this->getMeta('title'),
                'description' => $this->getMeta('description'),
                'subtype' => array_merge($this->getSubtype()->toDataSource($mixins), [
                    // I don't like using html here, but if I use text it adds an unnecessary and ugly <a> tag
                    'display' => 'html',
                    'html' => $this->getSubtype()->getName()
                ]),
                'submitted' => $this->getSubmittedTime(),
                'requested_time' => count($requested_times) > 0 ? $requested_times[0] : null,
                'first_time' => CoreUtils::happyTime(($first_time ? $first_time : 0)),
                'num_episodes' => [
                    'display' => 'text',
                    'value' => count($this->timeslots),
                    'url' => URLUtils::makeURL(
                        'Scheduler',
                        'listTimeslots',
                        ['show_season_id' => $this->getID()]
                    ),
                ],
                'editlink' => [
                    'display' => 'icon',
                    'value' => 'pencil',
                    'title' => 'Edit Season',
                    'url' => URLUtils::makeURL(
                        'Scheduler',
                        'editSeason',
                        ['seasonid' => $this->getID()]
                    ),
                ],
                'allocatelink' => [
                    'display' => 'icon',
                    'value' => 'pencil',
                    'title' => 'Edit Application or Allocate Season',
                    'url' => URLUtils::makeURL(
                        'Scheduler',
                        'allocate',
                        ['show_season_id' => $this->getID()]
                    ),
                ],
                'rejectlink' => [
                    'display' => 'icon',
                    'value' => 'trash',
                    'title' => 'Reject Application',
                    'url' => URLUtils::makeURL(
                        'Scheduler',
                        'reject',
                        ['show_season_id' => $this->getID()]
                    ),
                ],
            ]
        );
    }

    /**
     * This is where some of the most important MyRadio stuff happens.
     * This is where an application for a presenter's dreams become reality...
     * or get crushed to pieces.
     *
     * @param array $params key=>value of the following parameters:
     *                      weeks: A key=>value away of weeks and whether to schedule (wk1 => 0, wk1=>1...)
     *                      time: The preference number of the show_season_requested_time that was selected, or -1
     *                      timecustom_day: Ignored if time is > -1
     *                      If time = -1, this is the day # to schedule for
     *                      timecustom_stime: Ignored if time is > -1
     *                      If time = -1, this is the start time to schedule for
     *                      timecustom_etime: Ignored if time is >-1
     *                      If time = -1, this is the *duration* to schedule for (not end time)
     *
     * @todo Validate timeslots are available before scheduling
     * @todo Email the user notifying them of scheduling
     * @todo Verify the timeslot is free before scheduling
     */
    public function schedule($params)
    {
        //Verify that the input time is valid
        if (!isset($params['time']) or !is_numeric($params['time'])) {
            throw new MyRadioException(
                'No valid Time was sent to the Scheduling Mapper.',
                400
            );
        }
        if ($params['time'] != -1 && !isset($this->requested_times[$params['time']])) {
            throw new MyRadioException(
                'The Time value sent is not a valid Requested Time Reference.',
                400
            );
        }
        //Verify the custom times are valid
        if ($params['time'] == -1 && (!isset($params['timecustom_day'])  //0 (monday) would fail an empty() test
            or !isset($params['timecustom_stime'])  //Same again with midnight (00:00)
            or empty($params['timecustom_etime']))
        ) {
            throw new MyRadioException('The Custom Time value sent is invalid.', 400);
        }
        //Okay, let's get to business
        //First, figure out what time things are happening
        if ($params['time'] != -1) {
            //Use the requested times value
            $req_time = $this->requested_times[$params['time']];
        } else {
            $req_time = [
                'day' => $params['timecustom_day'],
                'start_time' => $params['timecustom_stime'],
                'duration' => $params['timecustom_etime'],
            ];
        }
        /*
         * Since terms start on the Monday, we just +1 day to it
         */
        $start_day = MyRadio_Scheduler::getTermStartDate() + ($req_time['day'] * 86400);

        //Now it's time to BEGIN to COMMIT!
        self::$db->query('BEGIN');
        /*
         * This will iterate over each week, decide if it should be scheduled,
         * then schedule it if it should. Simples.
         */
        $times = '';
        for ($i = 1; $i <= 10; ++$i) {
            if (isset($params['weeks']['wk'.$i]) && $params['weeks']['wk'.$i] == 1) {
                $day_start = $start_day + (($i - 1) * 7 * 86400);
                $gmt_show_time = $day_start + $req_time['start_time'];

                $dst_offset = timezone_offset_get(timezone_open(Config::$timezone), date_create('@'.$gmt_show_time));

                if ($dst_offset !== false) {
                    $show_time = $gmt_show_time - $dst_offset;
                } else {
                    $show_time = $gmt_show_time;
                }

                $conflict = MyRadio_Scheduler::getScheduleConflict($show_time, $show_time + $req_time['duration']);
                if (!empty($conflict)) {
                    self::$db->query('ROLLBACK');
                    throw new MyRadioException(
                        'A show is already scheduled for this time: '.print_r($conflict, true),
                        400
                    );
                }

                //This week is due to be scheduled! QUERY! QUERY!
                $r = self::$db->fetchAll(
                    'INSERT INTO schedule.show_season_timeslot
                    (show_season_id, start_time, duration, memberid, approvedid)
                    VALUES ($1, $2, $3, $4, $5) RETURNING show_season_timeslot_id',
                    [
                        $this->season_id,
                        CoreUtils::getTimestamp($show_time),
                        $req_time['duration'],
                        $this->owner->getID(),
                        $_SESSION['memberid'],
                    ]
                );
                if (empty($r)) {
                    throw new MyRadioException('Failed to schedule timeslot.', 500);
                }
                $this->timeslots[] = $r[0]['show_season_timeslot_id'];
                $times .= CoreUtils::happyTime($show_time)."\n"; //Times for the email

                // Clear the Schedule cache for this week
                $weekAndYear = CoreUtils::getYearAndWeekNo($show_time);
                self::$cache->delete('MyRadioScheduleFor'.$weekAndYear[0].'W'.$weekAndYear[1]);
            }
        }
        //COMMIT
        self::$db->query('COMMIT');
        $this->updateCacheObject();
        //Email the user
        /*
         * @todo Make this nicer and configurable and stuff
         */
        $message = '
Hello,

  Please note that one of your shows has been allocated the following timeslots
  on the '.Config::$short_name." Schedule:

$times

  Remember that except in exceptional circumstances, you must give at least
  48 hours notice for cancelling your show as part of your presenter contract.
  If you do not do this for two shows in one season, all other shows are forfeit
  and may be cancelled.

  You can cancel a timeslot by going to:
    My Shows -> Seasons for Show -> Timeslots for Season
  and then selecting cancel for the particular time.
  ".URLUtils::makeURL('Scheduler', 'myShows').'

  If you have any questions about your application, direct them to pc@'.Config::$email_domain.'

  ~ '.Config::$short_name.' Scheduling Legume';

        if (!empty($times)) {
            MyRadioEmail::sendEmailToUser($this->owner, $this->getMeta('title').' Scheduled', $message);
        }
    }

    /**
     * Deletes all future occurances of a Timeslot for this Season.
     */
    public function cancelRestOfSeason()
    {
        //Get a list of timeslots that will be cancelled and email the creditors
        $timeslots = $this->getFutureTimeslots();
        if (empty($timeslots)) {
            return;
        }

        $timeslot_str = "\r\n";
        foreach ($timeslots as $timeslot) {
            $timeslot_str .= CoreUtils::happyTime("{$timeslot['start_time']}\r\n");
        }

        $email = 'Please note that your show, '
            . $this->getMeta('title')
            . ' has been cancelled for the rest of the current Season. This is the following timeslots: '
            . $timeslot_str
            . "\r\n\r\n";
        $email .= "Regards\r\n" . Config::$long_name . ' Programming Team';

        foreach ($this->getShow()->getCredits() as $credit) {
            $u = MyRadio_User::getInstance($credit);
            MyRadioEmail::sendEmailToUser($u, 'Show Cancelled', $email);
        }

        $r = (bool) self::$db->query(
            'DELETE FROM schedule.show_season_timeslot WHERE show_season_id=$1 AND start_time >= NOW()',
            [$this->getID()]
        );
        $this->updateCacheObject();
        return $r;
    }

    /**
     * Returns an array of Timeslots in the future for this Season as follows:
     * show_season_timeslot_id
     * start_time
     * duration.
     *
     * @todo Refactor to return MyRadio_Timeslot objects
     */
    public function getFutureTimeslots()
    {
        return self::$db->fetchAll(
            'SELECT show_season_timeslot_id, start_time, duration FROM schedule.show_season_timeslot
            WHERE show_season_id=$1 AND start_time >= NOW()',
            [$this->getID()]
        );
    }

    /**
     * Returns the start time of the first Timeslot in this season.
     *
     * @return int
     */
    public function getFirstTime()
    {
        if (sizeof($this->timeslots) > 0) {
            return MyRadio_Timeslot::getInstance($this->timeslots[0])->getStartTime();
        } else {
            return false;
        }
    }

    /**
     * Returns all Timeslots for this Season.
     *
     * @return MyRadio_Timeslot[]
     */
    public function getAllTimeslots()
    {
        return MyRadio_Timeslot::resultSetToObjArray($this->timeslots);
    }

    /**
     * Returns the percentage of Timeslots in this Season that at least one User
     * has signed into.
     *
     * @return [float, int]
     */
    public function getAttendanceInfo()
    {
        $signed_in = 0;
        $total = 0;
        foreach ($this->getAllTimeslots() as $ts) {
            if ($ts->getStartTime() > time()) {
                continue;
            }
            ++$total;
            foreach ($ts->getSigninInfo() as $info) {
                if (!empty($info['signedby'])) {
                    ++$signed_in;
                    break;
                }
            }
        }

        if ($total === 0) {
            return [100, 0];
        }

        return [($signed_in / $total) * 100, $total - $signed_in];
    }

    /**
     * Searches searchable *text* metadata for the specified value. Does not work for image metadata.
     *
     * @todo effective_from/to not yet implemented
     *
     * @param string $query          The query value.
     * @param array  $string_keys    The metadata keys to search
     * @param int    $effective_from UTC Time to search from.
     * @param int    $effective_to   UTC Time to search to.
     *
     * @return array The shows that match the search terms
     */
    public static function searchMeta($query, $string_keys = null, $effective_from = null, $effective_to = null)
    {
        if (is_null($string_keys)) {
            $string_keys = ['title', 'description', 'tag'];
        }

        $r = parent::searchMetaBase(
            $query,
            $string_keys,
            $effective_from,
            $effective_to,
            'schedule.season_metadata',
            'show_season_id'
        );
        return self::resultSetToObjArray($r);
    }
}
