<?php

/**
 * Provides the Season class for MyRadio
 * @package MyRadio_Scheduler
 */

namespace MyRadio\ServiceAPI;

use \MyRadio\MyRadioException;
use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\MyRadio\MyRadioForm, \MyRadio\MyRadio\MyRadioFormField;
use \MyRadio\MyRadioEmail;

/**
 * The Season class is used to create, view and manupulate Seasons within the new MyRadio Scheduler Format
 * @version 20130814
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @package MyRadio_Scheduler
 * @uses \Database
 * @uses \MyRadio_Show
 */
class MyRadio_Season extends MyRadio_Metadata_Common
{
    private $season_id;
    private $show_id;
    private $term_id;
    private $submitted;
    private $owner;
    private $timeslots;
    private $requested_times = [];
    private $requested_weeks = [];
    private $season_num;

    protected function __construct($season_id)
    {
        $this->season_id = $season_id;
        //Init Database
        self::initDB();

        //Get the basic info about the season
        $result = self::$db->fetchOne(
            'SELECT show_id, termid, submitted, memberid, (
                SELECT array(
                    SELECT metadata_key_id FROM schedule.season_metadata
                    WHERE show_season_id=$1 AND effective_from <= NOW()
                    AND (effective_to IS NULL OR effective_to >= NOW())
                    ORDER BY effective_from, season_metadata_id
                )
            ) AS metadata_types, (
                SELECT array(
                    SELECT metadata_value FROM schedule.season_metadata
                    WHERE show_season_id=$1 AND effective_from <= NOW()
                    AND (effective_to IS NULL OR effective_to >= NOW())
                    ORDER BY effective_from, season_metadata_id
                )
            ) AS metadata, (
                SELECT array(
                    SELECT requested_day FROM schedule.show_season_requested_time
                    WHERE show_season_id=$1 ORDER BY preference ASC
                )
            ) AS requested_days, (
                SELECT array(
                    SELECT start_time FROM schedule.show_season_requested_time
                    WHERE show_season_id=$1
                    ORDER BY preference ASC
                )
            ) AS requested_start_times, (
                SELECT array(
                    SELECT duration FROM schedule.show_season_requested_time
                    WHERE show_season_id=$1
                    ORDER BY preference ASC
                )
            ) AS requested_durations, (
                SELECT array(
                    SELECT show_season_timeslot_id FROM schedule.show_season_timeslot
                    WHERE show_season_id=$1
                    ORDER BY start_time ASC
                )
            ) AS timeslots, (
                SELECT array(
                    SELECT week FROM schedule.show_season_requested_week WHERE show_season_id=$1
                )
            ) AS requested_weeks, (
                SELECT COUNT(*) FROM schedule.show_season
                WHERE show_id=(SELECT show_id FROM schedule.show_season WHERE show_season_id=$1)
                AND show_season_id<=$1
                AND show_season_id IN (SELECT show_season_id FROM schedule.show_season_timeslot)
            ) AS season_num
            FROM schedule.show_season WHERE show_season_id=$1',
            [$season_id]
        );
        if (empty($result)) {
            //Invalid Season
            throw new MyRadioException('The MyRadio_Season with instance ID #' . $season_id . ' does not exist.');
        }

        //Deal with the easy bits
        $this->owner = MyRadio_User::getInstance($result['memberid']);
        $this->show_id = (int) $result['show_id'];
        $this->submitted = strtotime($result['submitted']);
        $this->term_id = (int) $result['termid'];
        $this->season_num = (int) $result['season_num'];

        $metadata_types = self::$db->decodeArray($result['metadata_types']);
        $metadata = self::$db->decodeArray($result['metadata']);
        //Deal with the metadata
        for ($i = 0; $i < sizeof($metadata_types); $i++) {
            if (self::isMetadataMultiple($metadata_types[$i])) {
                $this->metadata[$metadata_types[$i]][] = $metadata[$i];
            } else {
                $this->metadata[$metadata_types[$i]] = $metadata[$i];
            }
        }

        //Requested Weeks
        $requested_weeks = self::$db->decodeArray($result['requested_weeks']);
        $this->requested_weeks = [];
        foreach ($requested_weeks as $requested_week) {
            $this->requested_weeks[] = intval($requested_week);
        }

        //Requested timeslots
        $requested_days = self::$db->decodeArray($result['requested_days']);
        $requested_start_times = self::$db->decodeArray($result['requested_start_times']);
        $requested_durations = self::$db->decodeArray($result['requested_durations']);

        for ($i = 0; $i < sizeof($requested_days); $i++) {
            $this->requested_times[] = [
                'day' => (int) $requested_days[$i],
                'start_time' => (int) $requested_start_times[$i],
                'duration' => self::$db->intervalToTime($requested_durations[$i])
            ];
        }

        //And now initiate timeslots
        $timeslots = self::$db->decodeArray($result['timeslots']);
        $this->timeslots = [];
        foreach ($timeslots as $timeslot) {
            $this->timeslots[] = MyRadio_Timeslot::getInstance($timeslot);
        }
    }

    /**
     * Creates a new MyRadio Season Application and returns an object representing it
     * @param Array $params An array of Seasons properties compatible with the Models/Scheduler/seasonfrm Form,
     * with a few additional potential customisation options:
     * weeks: An Array of weeks, keyed wk1-10, representing the requested week<br>
     * times: a 2D Array of:<br>
     *    day: An Array of one or more requested days, 0 being Monday, 6 being Sunday. Corresponds to (s|e)time<br>
     *    stime: An Array of sizeof(day) times, represeting the time of day the show should start<br>
     *    etime: An Array of sizeof(day) times, represeting the time of day the show should end<br>
     * description: A description of this Season of the Show, in addition to the Show description<br>
     * tags: A string of 0 or more space-seperated tags this Season relates to, in addition to the Show tags<br>
     * show_id: The ID of the Show to assign the application to
     * termid: The ID of the term being applied for. Defaults to the current Term
     *
     * weeks, day, stime, etime, show_id are all required fields
     *
     * As this is the initial creation, all tags are <i>approved</i> by the submitter so the Season has some initial values
     *
     * @throws MyURYException
     */
    public static function create($params = [])
    {
        //Validate input
        $required = ['show_id', 'weeks', 'times'];
        foreach ($required as $field) {
            if (!isset($params[$field])) {
                throw new MyRadioException('Parameter ' . $field . ' was not provided.', 400);
            }
        }

        /**
         * Select an appropriate value for $term_id
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
                MyRadio_User::getInstance()->getID()
            ],
            true
        );

        $season_id = $season_create_result[0];

        //Now let's allocate store the requested weeks for a term
        for ($i = 1; $i <= 10; $i++) {
            if ($params['weeks']["wk$i"]) {
                self::$db->query('INSERT INTO schedule.show_season_requested_week (show_season_id, week) VALUES ($1, $2)', [$season_id, $i], true);
            }
        }

        //Now for requested times
        for ($i = 0; $i < sizeof($params['times']['day']); $i++) {
            //Deal with the possibility of a show from 11pm to midnight etc.
            /**
             * @todo make this not be completely stupid
             */
            if ($params['times']['stime'][$i] < $params['times']['etime'][$i]) {
                $interval = CoreUtils::makeInterval($params['times']['stime'][$i], $params['times']['etime'][$i]);
            } else {
                $interval = CoreUtils::makeInterval($params['times']['stime'][$i], $params['times']['etime'][$i] + 86400);
            }

            //Enter the data
            self::$db->query(
                'INSERT INTO schedule.show_season_requested_time
                (requested_day, start_time, preference, duration, show_season_id)
                VALUES ($1, $2, $3, $4, $5)',
                [
                    $params['times']['day'][$i],
                    $params['times']['stime'][$i],
                    $i,
                    $interval,
                    $season_id
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
                    MyRadio_User::getInstance()->getID()
                ],
                true
            );
        }

        //Same with tags
        if (!empty($params['tags'])) {
            $tags = explode(' ', $params['tags']);
            foreach ($tags as $tag) {
                if (empty($tag)) {
                    continue;
                }
                self::$db->query(
                    'INSERT INTO schedule.season_metadata
                    (metadata_key_id, show_season_id, metadata_value, effective_from, memberid, approvedid)
                    VALUES ($1, $2, $3, NOW(), $4, $4)',
                    [
                        self::getMetadataKey('tag'),
                        $season_id,
                        $tag,
                        MyRadio_User::getInstance()->getID()
                    ],
                    true
                );
            }
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
        for ($i = 1; $i <= 10; $i++) {
            $weeks[] = new MyRadioFormField(
                'wk' . $i,
                MyRadioFormField::TYPE_CHECK,
                ['label' => 'Week ' . $i, 'required' => false]
            );
        }

        return (
            new MyRadioForm(
                'sched_season',
                'Scheduler',
                'editSeason',
                [
                    'debug' => true,
                    'title' => 'Create Season'
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
                    'label' => 'Schedule for Weeks'
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
                        )
                    ]
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
                    'required' => false
                ]
            )
        )->addField(
            new MyRadioFormField(
                'tags',
                MyRadioFormField::TYPE_TEXT,
                [
                    'label' => 'Tags',
                    'explanation' => 'A set of keywords to describe this Season. These will be added onto the '
                        .'Tags you already have set for the Show.',
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
        return self::getForm()
            ->setTitle('Edit Season')
            ->editMode(
                $this->getID(),
                [
                    'title' => $this->getMeta('title'),
                    'description' => $this->getMeta('description'),
                    'tags' => implode(' ', $this->getMeta('tag')),
                    'credits.member' => array_map(
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
                    )
                ]
            );
    }

    public function getAllocateForm()
    {
        $form = new MyRadioForm(
            'sched_allocate',
            'Scheduler',
            'allocate',
            [
                'title' => 'Allocate Timeslots to Season',
                'template' => 'Scheduler/allocate.twig'
            ]
        );

        //Set up the weeks checkboxes
        $weeks = [];
        for ($i = 1; $i <= 10; $i++) {
            $weeks[] = new MyRadioFormField(
                'wk' . $i,
                MyRadioFormField::TYPE_CHECK,
                [
                    'label' => 'Week ' . $i,
                    'required' => false,
                    'options' => ['checked' => in_array($i, $this->getRequestedWeeks())]
                ]
            );
        }

        //Set up the requested times radios
        $times = [];
        $i = 0;
        foreach ($this->getRequestedTimesAvail() as $time) {
            $times[] = [
                'value' => $i,
                'text' => $time['time'] . ' ' . $time['info'],
                'disabled' => $time['conflict'],
                'class' => $time['conflict'] ? 'ui-state-error' : ''
            ];
            $i++;
        }

        $times[] = ['value' => -1, 'text' => 'Other (Choose below)'];

        $form->addField(
            new MyRadioFormField(
                'weeks',
                MyRadioFormField::TYPE_CHECKGRP,
                [
                    'options' => $weeks,
                    'label' => 'Schedule for Weeks'
                ]
            )
        )->addField(
            new MyRadioFormField(
                'time',
                MyRadioFormField::TYPE_RADIO,
                [
                    'options' => $times,
                    'label' => 'Timeslot',
                    'required' => false
                ]
            )
        )->addField(
            new MyRadioFormField(
                'timecustom_day',
                MyRadioFormField::TYPE_DAY,
                [
                    'label' => 'Other Day: ',
                    'required' => false
                ]
            )
        )->addField(
            new MyRadioFormField(
                'timecustom_stime',
                MyRadioFormField::TYPE_TIME,
                [
                    'label' => 'from',
                    'required' => false
                ]
            )
        )->addField(
            new MyRadioFormField(
                'timecustom_etime',
                MyRadioFormField::TYPE_TIME,
                [
                    'label' => 'duration',
                    'required' => false,
                    'value' => '01:00'
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
                'doReject',
                [
                    'debug' => false,
                    'title' => 'Reject Season Application'
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
                        .' application and resubmit.'
                ]
            )
        )->addField(
            new MyRadioFormField(
                'notify_user',
                MyRadioFormField::TYPE_CHECK,
                [
                    'label' => 'Notify the Applicant via Email?',
                    'options' => ['checked' => true],
                    'required' => false
                ]
            )
        );
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
     * @return MyRadio_Season[]
     */
    public static function getAllSeasonsInTerm($term_id)
    {
        return self::resultSetToObjArray(self::$db->fetchColumn(
            'SELECT show_season_id FROM schedule.show_season WHERE termid=$1',
            [$term_id]
        ));
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
     * @param String $reason Why the application was rejected
     * @param bool $notify_user If true, all creditors will be notified about the rejection.
     */
    public function reject($reason, $notify_user = true)
    {
        if ($this->submitted == null) {
            return false;
        }
        self::$db->query('BEGIN');
        self::$db->query('UPDATE schedule.show_season SET submitted=NULL WHERE show_season_id=$1', [$this->getID()], true);
        $this->submitted = null;

        $this->setMeta('reject-reason', $reason);

        if ($notify_user) {
            MyRadioEmail::sendEmailToUserSet(
                $this->getShow()->getCreditObjects(),
                $this->getMeta('title') . ' Application Rejected',
                <<<EOT
Hi #NAME,

Your application for a season of a show was rejected by our programming team, for the reason given below:

$reason

You can reapply online at any time, or for more information, email pc@ury.org.uk.

~ {Config::$short_name} Scheduling Legume
EOT
            );
        }

        self::$db->query('COMMIT');
    }

    public function getMeta($meta_string)
    {
        $key = self::getMetadataKey($meta_string);
        if (isset($this->meta[$key])) {
            return $this->meta[$key];
        } else {
            return $this->getShow()->getMeta($meta_string);
        }
    }

    /**
     * Alias for getCredits($this->getShow()), which enables credits to be
     * automatically inherited from the show.
     *
     * @return Array[]
     */
    public function getCredits()
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
     * @param String $string_key The metadata key
     * @param mixed $value The metadata value. If key is_multiple and value is an array, will create instance
     * for value in the array.
     * @param int $effective_from UTC Time the metavalue is effective from. Default now.
     * @param int $effective_to UTC Time the metadata value is effective to. Default NULL (does not expire).
     * @param null $table No action. Used for compatibility with parent.
     * @param null $pkey No action. Used for compatibility with parent.
     */
    public function setMeta($string_key, $value, $effective_from = null, $effective_to = null, $table = null, $pkey = null)
    {
        $r = parent::setMeta($string_key, $value, $effective_from, $effective_to, 'schedule.season_metadata', 'show_season_id');
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

    public function getWebpage()
    {
        return 'http://ury.org.uk/show/' . $this->getShow()->getID() . '/' . $this->getID();
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
        date_default_timezone_set('UTC');
        $stime = date(' H:i', $time['start_time']);
        $etime = date('H:i', $time['start_time'] + $time['duration']);
        date_default_timezone_set('Europe/London');

        return self::getDayNameFromID($time['day']) . $stime . ' - ' . $etime;
    }

    private function getDayNameFromID($dow)
    {
        $days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

        return $days[$dow];
    }

    /**
     * Returns a 2D array:
     * time: Value as per getRequestedTimes()
     * conflict: True if one or more of requested weeks already have a booking that time
     * info: If True above, will have human-readable why-it-is-a-conflict details. It may also contain information about
     * "warnings" - conflicts on weeks this show isn't planned to be aired
     * @todo The Warnings part above
     * @todo Discuss efficiency of this algorithm
     */
    public function getRequestedTimesAvail()
    {
        $return = [];
        foreach ($this->requested_times as $time) {
            //Check for existence of shows in requested times
            $conflicts = MyRadio_Scheduler::getScheduleConflicts($this->term_id, $time);
            $warnings = [];
            foreach ($conflicts as $wk => $sid) {
                if (!in_array($wk, $conflicts)) {
                    //This isn't actually a conflict because the week isn't requested by the user
                    $warnings[$wk] = $sid;
                    unset($conflicts[$wk]);
                }
            }
            //If there's a any conflicts, let's make the nice explanation
            if (!empty($conflicts)) {
                $names = '';
                $weeks = ' on weeks ';
                $week_num = -10;
                //Count the number of conflicts with season ids
                $sids = [];
                foreach ($conflicts as $k => $v) {
                    /**
                     * @todo - figure out why this loop includes 0 and 11 sometimes
                     * @todo Work on weeks text output - duplicates/overlaps
                     */
                    if ($k > 10 || $k < 1) {
                        continue;
                    }
                    $sids[$v]++;
                    //Work out blocked weeks
                    if ($k == ++$week_num) {
                        //Continuation of previous sequence
                        $week_num = $k;
                        if ($k == 10) {
                            $weeks .= '-10';
                        }
                    } else {
                        //New sequence
                        if ($week_num > 0) {
                            $weeks .= '-' . $week_num . ', ';
                        }
                        $weeks .= $k;
                        $week_num = $k;
                    }
                }
                //Iterate over every conflicted show and log
                foreach ($sids as $k => $v) {
                    //Get the show name and store it
                    if ($names !== '') {
                        $names .= ', ';
                    }
                    $names .= self::getInstance($k)->getMeta('title');
                }
                $return[] = ['time' => self::formatTimeHuman($time), 'conflict' => true, 'info' => 'Conflicts with ' . $names . $weeks];
            } else {
                //No conflicts
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
     * @return int
     */
    public function getSeasonNumber()
    {
        return $this->season_num;
    }

    public function toDataSource($full = true)
    {
        return array_merge($this->getShow()->toDataSource(false), [
            'id' => $this->getID(),
            'season_num' => $this->getSeasonNumber(),
            'title' => $this->getMeta('title'),
            'description' => $this->getMeta('description'),
            'submitted' => $this->getSubmittedTime(),
            'requested_time' => sizeof($this->getRequestedTimes()) === 0 ? null : $this->getRequestedTimes()[0],
            'first_time' => (isset($this->timeslots[0]) && is_object($this->timeslots[0]) ? CoreUtils::happyTime($this->timeslots[0]->getStartTime()) : 'Not Scheduled'),
            'num_episodes' => [
                'display' => 'text',
                'value' => sizeof($this->timeslots),
                'url' => CoreUtils::makeURL('Scheduler', 'listTimeslots', ['show_season_id' => $this->getID()])
            ],
            'allocatelink' => [
                'display' => 'icon',
                'value' => 'script',
                'title' => 'Edit Application or Allocate Season',
                'url' => CoreUtils::makeURL('Scheduler', 'allocate', ['show_season_id' => $this->getID()])
            ],
            'rejectlink' => [
                'display' => 'icon',
                'value' => 'trash',
                'title' => 'Reject Application',
                'url' => CoreUtils::makeURL('Scheduler', 'reject', ['show_season_id' => $this->getID()])
            ]
        ]);
    }

    /**
     * This is where some of the most important MyRadio stuff happens.
     * This is where an application for a presenter's dreams become reality...
     * or get crushed to pieces.
     * @param Array $params key=>value of the following parameters:
     * weeks: A key=>value away of weeks and whether to schedule (wk1 => 0, wk1=>1...)
     * time: The preference number of the show_season_requested_time that was selected, or -1
     * timecustom_day: Ignored if time is > -1
     * If time = -1, this is the day # to schedule for
     * timecustom_stime: Ignored if time is > -1
     * If time = -1, this is the start time to schedule for
     * timecustom_etime: Ignored if time is >-1
     * If time = -1, this is the *duration* to schedule for (not end time)
     *
     * @todo Validate timeslots are available before scheduling
     * @todo Email the user notifying them of scheduling
     * @todo Verify the timeslot is free before scheduling
     */
    public function schedule($params)
    {
        date_default_timezone_set('UTC');
        //Verify that the input time is valid
        if (!isset($params['time']) or !is_numeric($params['time'])) {
            throw new MyRadioException('No valid Time was sent to the Scheduling Mapper.', MyRadioException::FATAL);
        }
        if ($params['time'] != -1 && !isset($this->requested_times[$params['time']])) {
            throw new MyRadioException('The Time value sent is not a valid Requested Time Reference.', MyRadioException::FATAL);
        }
        //Verify the custom times are valid
        if ($params['time'] == -1 && (
            !isset($params['timecustom_day']) or //0 (monday) would fail an empty() test
            !isset($params['timecustom_stime']) or //Same again with midnight (00:00)
            empty($params['timecustom_etime']))
        ) {
            throw new MyRadioException('The Custom Time value sent is invalid.', MyRadioException::FATAL);
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
                'duration' => $params['timecustom_etime']
            ];
        }
        /**
         * Since terms start on the Monday, we just +1 day to it
         */
        $start_day = MyRadio_Scheduler::getTermStartDate(MyRadio_Scheduler::getActiveApplicationTerm())
            + ($req_time['day'] * 86400);

        $start_time = date('H:i:s', $req_time['start_time']);

        //Now it's time to BEGIN to COMMIT!
        self::$db->query('BEGIN');
        /**
         * This will iterate over each week, decide if it should be scheduled,
         * then schedule it if it should. Simples.
         */
        $times = '';
        for ($i = 1; $i <= 10; $i++) {
            if (isset($params['weeks']['wk' . $i]) && $params['weeks']['wk' . $i] == 1) {
                $day_start = $start_day + (($i - 1) * 7 * 86400);
                $show_time = date('d-m-Y ', $day_start) . $start_time;

                /**
                 * @todo 1 is subtracted from the duration in the conflict checker here,
                 * as shows last precisely an hour, not 59m59s. Should we do something
                 * nicer here?
                 */
                $conflict = MyRadio_Scheduler::getScheduleConflict($day_start + $req_time['start_time'], $day_start + $start_time + $req_time['duration'] - 1);
                //print_r($conflict);
                //Disable because it doesn't fucking work.
                /*         * if (!empty($conflict)) {
                  self::$db->query('ROLLBACK');
                  throw new MyRadioException('A show is already scheduled for this time: '.print_r($conflict, true));
                  exit;
                  } */

                //This week is due to be scheduled! QUERY! QUERY!
                $r = self::$db->fetchAll(
                    'INSERT INTO schedule.show_season_timeslot
                    (show_season_id, start_time, duration, memberid, approvedid)
                    VALUES ($1, $2, $3, $4, $5) RETURNING show_season_timeslot_id',
                    [
                        $this->season_id,
                        $show_time,
                        $req_time['duration'],
                        $this->owner->getID(),
                        $_SESSION['memberid']
                    ]
                );
                $this->timeslots[] = MyRadio_Timeslot::getInstance($r[0]['show_season_timeslot_id']);
                $times .= CoreUtils::happyTime($show_time)."\n"; //Times for the email
            }
        }
        //COMMIT
        self::$db->query('COMMIT');
        $this->updateCacheObject();
        //Email the user
        /**
         * @todo Make this nicer and configurable and stuff
         */
        $message = "
Hello,

  Please note that one of your shows has been allocated the following timeslots on the ".Config::$short_name." Schedule:

$times

  Remember that except in exceptional circumstances, you must give at least 48 hours notice for cancelling your show as part of your presenter contract. If you do not do this for two shows in one season, all other shows are forfeit and may be cancelled.

  If you have any questions about your application, direct them to pc@ury.org.uk

  ~ ".Config::$short_name." Scheduling Legume";

        if (!empty($times)) {
            MyRadioEmail::sendEmailToUser($this->owner, $this->getMeta('title') . ' Scheduled', $message);
        }

        date_default_timezone_set(Config::$timezone);
    }

    /**
     * Deletes all future occurances of a Timeslot for this Season
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

        $email = 'Please note that your show, ' . $this->getMeta('title') . ' has been cancelled for the rest of the current Season. This is the following timeslots: ' . $timeslot_str;
        $email .= "\r\n\r\nRegards\r\n" . Config::$long_name . " Programming Team";

        foreach ($this->getShow()->getCredits() as $credit) {
            $u = MyRadio_User::getInstance($credit);
            MyRadioEmail::sendEmailToUser($u, 'Show Cancelled', $email);
        }

        $r = (bool) self::$db->query('DELETE FROM schedule.show_season_timeslot WHERE show_season_id=$1 AND start_time >= NOW()', [$this->getID()]);

        $this->updateCacheObject();

        return $r;
    }

    /**
     * Returns an array of Timeslots in the future for this Season as follows:
     * show_season_timeslot_id
     * start_time
     * duration
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
     * Returns all Timeslots for this Season
     * @return MyRadio_Timeslot[]
     */
    public function getAllTimeslots()
    {
        return $this->timeslots;
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
            $total++;
            foreach ($ts->getSigninInfo() as $info) {
                if (!empty($info['signedby'])) {
                    $signed_in++;
                    break;
                }
            }
        }

        if ($total === 0) {
            return [100, 0];
        }

        return [($signed_in / $total) * 100, $total - $signed_in];
    }
}
