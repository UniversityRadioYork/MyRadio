<?php

/**
 * Provides the Timeslot class for MyRadio.
 */

namespace MyRadio\ServiceAPI;

use MyRadio\Config;
use MyRadio\MyRadioException;
use MyRadio\MyRadio\CoreUtils;
use MyRadio\MyRadio\URLUtils;
use MyRadio\MyRadioEmail;
use MyRadio\MyRadio\MyRadioForm;
use MyRadio\MyRadio\MyRadioFormField;
use MyRadio\NIPSWeb\NIPSWeb_TimeslotItem;
use MyRadio\NIPSWeb\NIPSWeb_BAPSUtils;
use MyRadio\SIS\SIS_Utils;

/**
 * The Timeslot class is used to view and manupulate Timeslot within the new MyRadio Scheduler Format.
 *
 * @todo Generally creation of bulk Timeslots is currently handled by the Season/Show classes, but this should change
 *
 * @uses \Database
 * @uses \MyRadio_Show
 */
class MyRadio_Timeslot extends MyRadio_Metadata_Common
{
    private $timeslot_id;
    private $start_time;
    private $duration;
    private $season_id;
    private $timeslot_num;
    protected $owner;
    protected $credits;

    protected function __construct($timeslot_id)
    {
        if (empty($timeslot_id)) {
            throw new MyRadioException('Timeslot ID must be provided.');
        }

        $this->timeslot_id = (int)$timeslot_id;
        //Init Database
        self::initDB();

        //Get the basic info about the timeslot
        // Note that credits have different metadata timeranges to text
        // This is annoying, but needs to be this way.
        $result = self::$db->fetchOne(
            'SELECT show_season_timeslot_id, show_season_id, start_time, duration, memberid, (
                SELECT array_to_json(array(
                    SELECT metadata_key_id FROM schedule.timeslot_metadata
                    WHERE show_season_timeslot_id=$1
                    AND effective_from < NOW()
                    AND (effective_to IS NULL OR effective_to > NOW())
                    ORDER BY effective_from, show_season_timeslot_id
                ))
            ) AS metadata_types, (
                SELECT array_to_json(array(
                    SELECT metadata_value FROM schedule.timeslot_metadata
                    WHERE show_season_timeslot_id=$1
                    AND effective_from < NOW()
                    AND (effective_to IS NULL OR effective_to > NOW())
                    ORDER BY effective_from, show_season_timeslot_id
                ))
            ) AS metadata, (
                SELECT COUNT(*) FROM schedule.show_season_timeslot
                WHERE show_season_id=(
                    SELECT show_season_id FROM schedule.show_season_timeslot
                    WHERE show_season_timeslot_id=$1
                )
                AND start_time<=(SELECT start_time FROM schedule.show_season_timeslot WHERE show_season_timeslot_id=$1)
            ) AS timeslot_num, (
                SELECT array_to_json(array(
                    SELECT creditid FROM schedule.show_credit
                    WHERE show_id=(
                        SELECT show_id FROM schedule.show_season_timeslot
                        JOIN schedule.show_season USING (show_season_id)
                        WHERE show_season_timeslot_id=$1
                    )
                    AND effective_from < (start_time + duration)
                    AND (effective_to IS NULL OR effective_to > start_time)
                    AND approvedid IS NOT NULL
                    ORDER BY show_credit_id
                ))
            ) AS credits, (
                SELECT array_to_json(array(
                    SELECT credit_type_id FROM schedule.show_credit
                    WHERE show_id=(
                        SELECT show_id FROM schedule.show_season_timeslot
                        JOIN schedule.show_season USING (show_season_id)
                        WHERE show_season_timeslot_id=$1
                    )
                    AND effective_from < (start_time + duration)
                    AND (effective_to IS NULL OR effective_to > start_time)
                    AND approvedid IS NOT NULL
                    ORDER BY show_credit_id
                ))
            ) AS credit_types
            FROM schedule.show_season_timeslot
            WHERE show_season_timeslot_id=$1',
            [$timeslot_id]
        );
        if (empty($result)) {
            //Invalid Season
            throw new MyRadioException(
                'The MyRadio_Timeslot with instance ID #' . $timeslot_id . ' does not exist.',
                400
            );
        }

        //Deal with the easy bits
        $this->timeslot_id = (int)$result['show_season_timeslot_id'];
        $this->season_id = (int)$result['show_season_id'];
        $this->start_time = strtotime($result['start_time']);
        $this->duration = $result['duration'];
        $this->owner = MyRadio_User::getInstance($result['memberid']);
        $this->timeslot_num = (int)$result['timeslot_num'];

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

        //Deal with the Credits arrays
        $credit_types = json_decode($result['credit_types']);
        $credits = json_decode($result['credits']);

        for ($i = 0; $i < sizeof($credits); ++$i) {
            if (empty($credits[$i])) {
                continue;
            }
            $this->credits[] = ['type' => (int)$credit_types[$i], 'memberid' => $credits[$i],
                'User' => MyRadio_User::getInstance($credits[$i]),];
        }
    }

    public function getMeta($meta_string)
    {
        $key = self::getMetadataKey($meta_string);
        if (isset($this->metadata[$key])) {
            return $this->metadata[$key];
        } else {
            return $this->getSeason()->getMeta($meta_string);
        }
    }

    public function getID()
    {
        return $this->timeslot_id;
    }

    public function getSeason()
    {
        return MyRadio_Season::getInstance($this->season_id);
    }

    /**
     * Get the microsite URI.
     *
     * @return string
     */
    public function getWebpage()
    {
        return '/schedule/shows/timeslots/' . $this->timeslot_id;
    }

    public function getPhoto()
    {
        return $this->getSeason()->getShow()->getShowPhoto();
    }

    /**
     * Get the Timeslot number - for the first Timeslot of a Season, this is 1, for the second it's 2 etc.
     *
     * @return int
     */
    public function getTimeslotNumber()
    {
        return $this->timeslot_num;
    }

    /**
     * Get the start time of the Timeslot as an integer since epoch.
     *
     * @return int
     */
    public function getStartTime()
    {
        return $this->start_time;
    }

    public function getDuration()
    {
        return $this->duration;
    }

    /**
     * Returns when the timeslot ends in epoch number form.
     *
     * @return int
     */
    public function getEndTime()
    {
        $duration = strtotime('1970-01-01 ' . $this->getDuration() . '+00');

        return $this->getStartTime() + $duration;
    }

    /**
     * Gets the Timeslot that is on after this.
     *
     * @param $filter defines a filter of show_type ids
     *
     * @return MyRadio_Timeslot|null If null, Jukebox is next.
     */
    public function getTimeslotAfter($filter = [1])
    {
        // lolphp http://php.net/manual/en/function.pg-query-params.php#71912
        $filter = '{' . implode(', ', $filter) . '}';

        $result = self::$db->fetchColumn(
            'SELECT show_season_timeslot_id
            FROM schedule.show_season_timeslot
            INNER JOIN schedule.show_season USING (show_season_id)
            INNER JOIN schedule.show USING (show_id)
            WHERE start_time >= $1 AND start_time <= $2
            AND show_type_id = ANY ($3)
            ORDER BY start_time ASC LIMIT 1',
            [
                CoreUtils::getTimestamp($this->getEndTime() - 300),
                CoreUtils::getTimestamp($this->getEndTime() + 300),
                $filter,
            ]
        );
        if (empty($result)) {
            return;
        } else {
            return self::getInstance($result[0]);
        }
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
     * @param string $string_key The metadata key
     * @param mixed $value The metadata value. If key is_multiple and value is an array, will create instance
     *                               for value in the array.
     * @param int $effective_from UTC Time the metavalue is effective from. Default now.
     * @param int $effective_to UTC Time the metadata value is effective to. Default NULL (does not expire).
     */
    public function setMeta($string_key, $value, $effective_from = null, $effective_to = null)
    {
        $r = parent::setMetaBase(
            $string_key,
            $value,
            $effective_from,
            $effective_to,
            'schedule.timeslot_metadata',
            'show_season_timeslot_id'
        );
        $this->updateCacheObject();

        return $r;
    }

    /**
     * Searches searchable *text* metadata for the specified value. Does not work for image metadata.
     *
     * @param string $query The query value.
     * @param array $string_keys The metadata keys to search
     * @param int $effective_from UTC Time to search from.
     * @param int $effective_to UTC Time to search to.
     *
     * @return array The shows that match the search terms
     * @todo effective_from/to not yet implemented
     *
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
            'schedule.timeslot_metadata',
            'show_season_timeslot_id'
        );
        return self::resultSetToObjArray($r);
    }

    /**
     * Serialises the timeslot. Merges with the parent season object as well.
     */
    public function toDataSource($mixins = [])
    {
        return array_merge(
            $this->getSeason()->toDataSource($mixins),
            [
                'timeslot_id' => $this->getID(),
                'timeslot_num' => $this->getTimeslotNumber(),
                'title' => $this->getMeta('title'),
                'description' => $this->getMeta('description'),
                'tags' => $this->getMeta('tag'),
                'time' => $this->getStartTime(),
                'start_time' => CoreUtils::happyTime($this->getStartTime()),
                'duration' => $this->getDuration(),
                'mixcloud_status' => $this->getMeta('upload_state'),
                'mixcloud_starttime' => $this->getMeta('upload_starttime'),
                'mixcloud_endtime' => $this->getMeta('upload_endtime'),
                'rejectlink' => [
                    'display' => 'icon',
                    'value' => 'trash',
                    'title' => 'Cancel Episode',
                    'url' => URLUtils::makeURL(
                        'Scheduler',
                        'cancelEpisode',
                        ['show_season_timeslot_id' => $this->getID()]
                    ),
                ],
            ]
        );
    }

    /**
     * Find the most messaged Timeslots.
     *
     * @param int $date If specified, only messages for timeslots since $date are counted.
     *
     * @return array An array of 30 Timeslots that have been put through toDataSource, with the addition of a msg_count
     *               key, referring to the number of messages sent to that show.
     */
    public static function getMostMessaged($date = 0)
    {
        $result = self::$db->fetchAll(
            'SELECT messages.timeslotid, count(*) as msg_count FROM sis2.messages
            LEFT JOIN schedule.show_season_timeslot ON messages.timeslotid=show_season_timeslot.show_season_timeslot_id
            WHERE show_season_timeslot.start_time > $1 GROUP BY messages.timeslotid ORDER BY msg_count DESC LIMIT 30',
            [CoreUtils::getTimestamp($date)]
        );

        $top = [];
        foreach ($result as $r) {
            $show = self::getInstance($r['timeslotid'])->toDataSource();
            $show['msg_count'] = intval($r['msg_count']);
            $top[] = $show;
        }

        return $top;
    }

    /**
     * Find the most listened Timeslots.
     *
     * @param int $date If specified, only messages for timeslots since $date are counted.
     *
     * @return array An array of 30 Timeslots that have been put through toDataSource, with the addition of a msg_count
     *               key, referring to the number of messages sent to that show.
     */
    public static function getMostListened($date = 0)
    {
        $key = 'stats_timeslot_mostlistened';
        if (($top = self::$cache->get($key)) !== false) {
            return $top;
        }

        $result = self::$db->fetchAll(
            'SELECT show_season_timeslot_id,
            (
                SELECT COUNT(*) FROM strm_log
                WHERE (starttime < start_time
                AND endtime >= start_time)
                OR (starttime >= start_time AND starttime < start_time + duration)
            ) AS listeners
            FROM schedule.show_season_timeslot WHERE start_time > $1
            ORDER BY listeners DESC LIMIT 30',
            [CoreUtils::getTimestamp($date)]
        );

        $top = [];
        foreach ($result as $r) {
            $show = self::getInstance($r['show_season_timeslot_id'])->toDataSource();
            $show['listeners'] = intval($r['listeners']);
            $top[] = $show;
        }

        self::$cache->set($key, $top, 86400);

        return $top;
    }

    /**
     * Returns the current Timeslot on air, if there is one.
     *
     * @param int $time Optional integer timestamp
     * @param $filter defines a filter of show_type ids
     *
     * @return MyRadio_Timeslot|null
     */
    public static function getCurrentTimeslot($time = null, $filter = [1])
    {
        self::initDB(); //First DB access for Timelord
        if ($time === null) {
            $time = time();
        }

        $filter = '{' . implode(', ', $filter) . '}'; // http://php.net/manual/en/function.pg-query-params.php#71912

        $result = self::$db->fetchColumn(
            'SELECT show_season_timeslot_id
            FROM schedule.show_season_timeslot
            INNER JOIN schedule.show_season USING (show_season_id)
            INNER JOIN schedule.show USING (show_id)
            WHERE start_time <= $1
            AND start_time + duration >= $1
            AND show_type_id = ANY ($2)',
            [CoreUtils::getTimestamp($time), $filter]
        );

        if (empty($result)) {
            return;
        } else {
            return self::getInstance($result[0]);
        }
    }

    /**
     * Gets the previous Timeslots before $time, in reverse chronological order.
     *
     * @param int $time
     * @param int $n defines the number of timeslots you want before this time.
     * @param     $filter defines a filter of show_type ids
     *
     * @return Array of MyRadio_Timeslots
     */
    public static function getPreviousTimeslots($time = null, $n = 1, $filter = [1])
    {
        // lolphp http://php.net/manual/en/function.pg-query-params.php#71912
        $filter = '{' . implode(', ', $filter) . '}';

        $result = self::$db->fetchAll(
            'SELECT show_season_timeslot_id
            FROM schedule.show_season_timeslot
            INNER JOIN schedule.show_season USING (show_season_id)
            INNER JOIN schedule.show USING (show_id)
            WHERE start_time < $1
            AND show_type_id = ANY ($3)
            ORDER BY start_time DESC
            LIMIT $2',
            [CoreUtils::getTimestamp($time), $n, $filter]
        );

        $timeslots = [];
        foreach ($result as $r) {
            $timeslots[] = self::getInstance($r['show_season_timeslot_id']);
        }
        return $timeslots;
    }

    /**
     * Gets the next Timeslot to start after $time.
     *
     * @param int $time
     * @param     $filter defines a filter of show_type ids
     *
     * @return MyRadio_Timeslot
     */
    public static function getNextTimeslot($time = null, $filter = [1])
    {
        // lolphp http://php.net/manual/en/function.pg-query-params.php#71912
        $filter = '{' . implode(', ', $filter) . '}';

        $result = self::$db->fetchColumn(
            'SELECT show_season_timeslot_id
            FROM schedule.show_season_timeslot
            INNER JOIN schedule.show_season USING (show_season_id)
            INNER JOIN schedule.show USING (show_id)
            WHERE start_time >= $1
            AND show_type_id = ANY ($2)
            ORDER BY start_time ASC
            LIMIT 1',
            [CoreUtils::getTimestamp($time), $filter]
        );

        if (empty($result)) {
            return;
        } else {
            return self::getInstance($result[0]);
        }
    }

    /**
     * Returns Timeslots scheduled for the given week number.
     *
     * "Weeks" are nine days - From the Sunday preceeding the week to the Monday
     * after the week ends, i.e. Sun/Mon/Tue/Wed/Thu/Fri/Sat/Sun/Mon.
     * A Timeslot that starts before the start of the period but ends during
     * will be included. The same is true for ones that end after the period.<br>
     * It is guaranteed that the results will be in order of start time.
     *
     * @param int $weekno An ISO-8601 Week Number (http://en.wikipedia.org/wiki/ISO_8601#Week_dates)
     * @param int $year Default to current Calendar year.
     *
     * @return MyRadio_Timeslot[]
     */
    public static function get9DaySchedule($weekno, $year = null)
    {
        self::wakeup();
        if ($year === null) {
            $year = (int)gmdate('Y');
        }

        if ($weekno < 10) {
            $weekno = '0' . $weekno;
        }

        $key = 'MyRadio9DayScheduleFor' . $year . 'W' . $weekno;
        $cache = self::$cache->get($key);
        if (!$cache) {
            $startOfWeek = strtotime($year . 'W' . $weekno);
            $sundayBefore = $startOfWeek - 86400; // 60 * 60 * 24
            $endOfMondayAfter = $startOfWeek + (86400 * 8) - 1; //Monday 23:59:59

            $startTimestamp = CoreUtils::getTimestamp($sundayBefore);
            $endTimestamp = CoreUtils::getTimestamp($endOfMondayAfter);

            $result = self::$db->fetchColumn(
                'SELECT show_season_timeslot_id
                FROM schedule.show_season_timeslot
                INNER JOIN schedule.show_season USING (show_season_id)
                INNER JOIN schedule.show USING (show_id)
                WHERE (
                    (start_time + duration >= $1 AND start_time + duration <= $2) OR
                    (start_time >= $1 AND start_time <= $2)
                )
                AND show_type_id = 1
                ORDER BY start_time ASC',
                [$startTimestamp, $endTimestamp]
            );

            $cache = self::resultSetToObjArray($result);

            self::$cache->set($key, $cache, 3600);
        }

        return $cache;
    }

    /**
     * Returns Timeslots scheduled for the given week number.
     *
     * Weeks are from Monday - Sunday (URY days start at 6am)
     * A Timeslot that starts before the start of the period but ends during
     * will be included. The same is true for ones that end after the period.<br>
     * It is guaranteed that the results will be in order of start time.
     *
     * @param int $weekno An ISO-8601 Week Number (http://en.wikipedia.org/wiki/ISO_8601#Week_dates)
     * @param int $year Default to current Calendar year.
     *
     * @return MyRadio_Timeslot[]
     */
    public static function getWeekSchedule($weekno, $year = null)
    {
        self::wakeup();
        if ($year === null) {
            $year = (int)gmdate('Y');
        }

        if ($weekno < 10) {
            $weekno = '0' . $weekno;
        }

        $key = 'MyRadioWeekScheduleFor' . $year . 'W' . $weekno;
        $cache = self::$cache->get($key);
        if (!$cache) {
            $startOfWeek = strtotime($year . 'W' . $weekno) + (60 * 60 * 6); // Monday 06:00:00
            $endOfWeek = $startOfWeek + (86400 * 7) - 1; //Next Monday 05:59:59

            $startTimestamp = CoreUtils::getTimestamp($startOfWeek);
            $endTimestamp = CoreUtils::getTimestamp($endOfWeek);

            $result = self::$db->fetchAll(
                'SELECT show_season_timeslot_id, EXTRACT(ISODOW FROM (start_time - interval \'6 hours\')) as day
                FROM schedule.show_season_timeslot
                INNER JOIN schedule.show_season USING (show_season_id)
                INNER JOIN schedule.show USING (show_id)
                WHERE (
                    (start_time + duration >= $1 AND start_time + duration <= $2) OR
                    (start_time >= $1 AND start_time <= $2)
                )
                AND show_type_id = 1
                ORDER BY start_time ASC',
                [$startTimestamp, $endTimestamp]
            );

            $schedule = [];

            foreach ($result as $item) {
                $schedule[$item['day']][] = self::getInstance($item['show_season_timeslot_id']);
            }

            $cache = $schedule;

            self::$cache->set($key, $cache, 3600);
        }

        return $cache;
    }

    /**
     * Returns the current timeslot, and the n after it, in a simplified
     * datasource format. Mainly intended for API use.
     *
     * @param int $time
     * @param int $n number of next shows to return
     * @param $filter defines a filter of show_type ids
     */
    public static function getCurrentAndNext($time = null, $n = 1, $filter = [1])
    {
        $isTerm = MyRadio_Scheduler::isTerm();
        $timeslot = self::getCurrentTimeslot($time, $filter);
        $next = self::getNextTimeslot($time, $filter);

        //Still display a show if there's one scheduled for whatever reason.
        if (!$isTerm && empty($timeslot)) {
            //We're outside term time.
            $response = [
                'current' => [
                    'title' => 'Off Air',
                    'desc' => 'We\'re not broadcasting right now, we\'ll be back next term.',
                    'photo' => Config::$offair_uri,
                    'end_time' => $next ? $next->getStartTime() : 'The End of Time',
                ],
            ];
        } elseif (empty($timeslot)) {
            //There's currently not a show on.
            $response = [
                'current' => [
                    'title' => Config::$short_name . ' Jukebox',
                    'desc' => 'There are currently no shows on right now, even our presenters
                                need a break. But it\'s okay, ' . Config::$short_name .
                        ' Jukebox has got you covered, playing the best music for your ears!',
                    'photo' => Config::$default_show_uri,
                    'end_time' => $next ? $next->getStartTime() : 'The End of Time',
                ],
            ];
        } else {
            //There's a show on!
            $response = [
                'current' => [
                    'title' => $timeslot->getMeta('title'),
                    'desc' => $timeslot->getMeta('description'),
                    'photo' => $timeslot->getPhoto(),
                    'start_time' => $timeslot->getStartTime(),
                    'end_time' => $timeslot->getEndTime(),
                    'presenters' => $timeslot->getPresenterString(),
                    'url' => $timeslot->getWebpage(),
                    'id' => $timeslot->getID(),
                ],
            ];
            $next = $timeslot->getTimeslotAfter($filter);
        }

        $lastnext = $timeslot;

        for ($i = 0; $i < $n; ++$i) {
            if (empty($next)) {
                if ($lastnext instanceof self) {
                    //There's not a next show, but there might be one later
                    $nextshow = self::getNextTimeslot($lastnext->getEndTime(), $filter);

                    $response['next'][] = [
                        'title' => Config::$short_name . ' Jukebox',
                        'desc' => 'There are currently no shows on right now, even our presenters
                                    need a break. But it\'s okay, ' . Config::$short_name .
                            ' Jukebox has got you covered, playing the best music for your ears!',
                        'photo' => Config::$default_show_uri,
                        'start_time' => $lastnext->getEndTime(),
                        'end_time' => $nextshow ? $nextshow->getStartTime() : 'The End of Time',
                    ];
                }
            } else {
                //There's a next show
                $response['next'][] = [
                    'title' => $next->getMeta('title'),
                    'desc' => $next->getMeta('description'),
                    'photo' => $next->getPhoto(),
                    'start_time' => $next->getStartTime(),
                    'end_time' => $next->getEndTime(),
                    'presenters' => $next->getPresenterString(),
                    'url' => $next->getWebpage(),
                    'id' => $next->getID(),
                ];
            }

            if ($next instanceof self) {
                $lastnext = $next;
                $next = $next->getTimeslotAfter($filter);
            } else {
                if ($lastnext instanceof self) {
                    $last = $next;
                    $next = self::getNextTimeslot($lastnext->getEndTime(), $filter);
                    $lastnext = $last;
                } else {
                    $lastnext = $next;
                    $next = [];
                }
            }
        }

        if (isset($response['next']) && sizeof($response['next']) === 1 && $n == 1) {
            $response['next'] = $response['next'][0];
        }

        return $response;
    }

    /**
     * Deletes this Timeslot from the Schedule, and everything associated with it.
     *
     * This is a proxy for several other methods, depending on the User and the current time:<br>
     * (1) If the User has Cancel Show Privileges, then they can remove it at any time, notifying Creditors
     *
     * (2) If the User is a Show Credit, and there are 48 hours or more until broadcast, they can remove it,
     *     notifying the PC
     *
     * (3) If the User is a Show Credit, and there are less than 48 hours until broadcast, they can send a request to
     *     the PC for removal, and it will be flagged as hidden from the Schedule - it will still count as a noshow
     *     unless (1) occurs
     *
     * @param string $reason , Why the episode was cancelled.
     *
     * @todo Make the smarter - check if it's a programming team person, in which case just do this, if it's not then if
     *       >48hrs away just do it but email programming, but <48hrs should hide it but tell prog to confirm reason
     * @todo Response codes? i.e. error/db or error/403 etc
     */
    public function cancelTimeslot($reason)
    {
        //Get if the User has permission to drop the episode
        if (MyRadio_User::getInstance()->hasAuth(AUTH_DELETESHOWS)) {
            //Yep, do an administrative drop
            $r = $this->cancelTimeslotAdmin($reason);
        } elseif ($this->getSeason()->getShow()->isCurrentUserAnOwner()) {
            //Get if the User is a Creditor
            //Yaay, depending on time they can do an self-service drop or cancellation request
            if ($this->getStartTime() > time() + (48 * 3600)) {
                //Self-service cancellation
                $r = $this->cancelTimeslotSelfService($reason);
            } else {
                //Emergency cancellation request
                $r = $this->cancelTimeslotRequest($reason);
            }
        } else {
            //They can't do this.
            return false;
        }

        return $r;
    }

    private function cancelTimeslotAdmin($reason)
    {
        $r = $this->deleteTimeslot();
        if (!$r) {
            return false;
        }

        $email = "Hi #NAME, \r\n\r\n Please note that an episode of your show, " . $this->getMeta('title')
            . ' has been cancelled by our Programming Team. The affected episode was at '
            . CoreUtils::happyTime($this->getStartTime())
            . "\r\n\r\n";
        $email .= "Reason: $reason\r\n\r\nRegards\r\n" . Config::$long_name . ' Programming Team';
        self::$cache->purge();

        MyRadioEmail::sendEmailToUserSet(
            $this->getSeason()->getShow()->getCreditObjects(),
            'Episode of ' . $this->getMeta('title') . ' Cancelled',
            $email
        );
        return true;
    }

    private function cancelTimeslotSelfService($reason)
    {
        $r = $this->deleteTimeslot();
        if (!$r) {
            return false;
        }

        $email1 = "Hi #NAME, \r\n\r\n You have requested that an episode of " . $this->getMeta('title')
            . ' is cancelled. The affected episode was at ' . CoreUtils::happyTime($this->getStartTime())
            . "\r\n\r\n";
        $email1 .= "Reason: $reason\r\n\r\nRegards\r\n" . Config::$long_name . ' Scheduler Robot';

        $email2 = $this->getMeta('title')
            . ' on ' . CoreUtils::happyTime($this->getStartTime())
            . ' was cancelled by a presenter because ' . $reason
            . "\r\n\r\n";
        $email2 .= "It was cancelled automatically as more than required notice was given.";

        MyRadioEmail::sendEmailToUserSet(
            $this->getSeason()->getShow()->getCreditObjects(),
            'Episode of ' . $this->getMeta('title') . ' Cancelled',
            $email1
        );
        MyRadioEmail::sendEmailToList(
            MyRadio_List::getByName('programming'),
            'Episode of ' . $this->getMeta('title') . ' Cancelled',
            $email2
        );
        return true;
    }

    private function cancelTimeslotRequest($reason)
    {
        $email = $this->getMeta('title')
            . ' on ' . CoreUtils::happyTime($this->getStartTime())
            . ' has requested cancellation because ' . $reason
            . "\r\n\r\n";
        $email .= "Due to the short notice, it has been passed to you for consideration. "
            . "To cancel the timeslot, visit ";
        $email .= URLUtils::makeURL(
            'Scheduler',
            'cancelEpisode',
            ['show_season_timeslot_id' => $this->getID(), 'reason' => base64_encode($reason)]
        );

        MyRadioEmail::sendEmailToList(MyRadio_List::getByName('presenting'), 'Show Cancellation Request', $email);
        return true;
    }

    /**
     * Deletes the timeslot. Nothing else. See the cancelTimeslot... methods for recommended removal usage.
     *
     * @return bool success/fail
     */
    private function deleteTimeslot()
    {
        $r = self::$db->query(
            'DELETE FROM schedule.show_season_timeslot WHERE show_season_timeslot_id=$1',
            [$this->getID()]
        );

        $this->updateCacheObject();
        return $r;
    }

    /**
     * Move this Timeslot to a new time.
     * @param $newStart
     * @param $newEnd
     */
    public function moveTimeslot($newStart, $newEnd)
    {
        $r = self::$db->query(
            'UPDATE schedule.show_season_timeslot
            SET start_time = $1, duration = $2
            WHERE show_season_timeslot_id = $3',
            [
                CoreUtils::getTimestamp($newStart),
                CoreUtils::makeInterval($newStart, $newEnd),
                $this->getID()
            ]
        );

        self::$cache->purge();
        return $r;
    }

    /**
     * This is the server-side implementation of the JSONON system for tracking Show Planner alterations.
     *
     * @param array[] $set A JSONON operation set
     */
    public function updateShowPlan($set)
    {
        $result = [];
        //Being a Database Transaction - this all succeeds, or none of it does
        self::$db->query('BEGIN');

        foreach ($set as $op) {
            switch ($op['op']) {
                case 'AddItem':
                    try {
                        //Is this a record or a manageditem?
                        $parts = explode('-', $op['id']);
                        if ($parts[0] === 'ManagedDB') {
                            //This is a managed item
                            $i = NIPSWeb_TimeslotItem::createManaged(
                                $this->getID(),
                                $parts[1],
                                $op['channel'],
                                $op['weight']
                            );
                        } else {
                            //This is a rec database track
                            $i = NIPSWeb_TimeslotItem::createCentral(
                                $this->getID(),
                                $parts[1],
                                $op['channel'],
                                $op['weight']
                            );
                        }
                    } catch (MyRadioException $e) {
                        $result[] = ['status' => false];
                        self::$db->query('ROLLBACK');

                        return $result;
                    }

                    $result[] = ['status' => true, 'timeslotitemid' => $i->getID()];
                    break;

                case 'MoveItem':
                    if (!is_numeric($op['timeslotitemid'])) {
                        $result[] = ['status' => false];
                        self::$db->query('ROLLBACK');

                        return $result;
                    }
                    $i = NIPSWeb_TimeslotItem::getInstance($op['timeslotitemid']);
                    if ($i->getChannel() != $op['oldchannel'] or $i->getWeight() != $op['oldweight']) {
                        $result[] = ['status' => false];
                        self::$db->query('ROLLBACK');

                        return $result;
                    } else {
                        $i->setLocation($op['channel'], $op['weight']);
                        $result[] = ['status' => true];
                    }
                    break;

                case 'RemoveItem':
                    if (!is_numeric($op['timeslotitemid'])) {
                        throw new MyRadioException($op['timeslotitemid'] . ' is invalid.', 500);
                    }
                    $i = NIPSWeb_TimeslotItem::getInstance($op['timeslotitemid']);
                    if ($i->getChannel() != $op['channel'] or $i->getWeight() != $op['weight']) {
                        $result[] = ['status' => false];
                        self::$db->query('ROLLBACK');

                        return $result;
                    } else {
                        $i->remove();
                        $result[] = ['status' => true];
                    }
                    break;
            }
        }

        self::$db->query('COMMIT');

        //Update the legacy baps show plans database
        $this->updateLegacyShowPlan();

        return $result;
    }

    private function updateLegacyShowPlan()
    {
        NIPSWeb_BAPSUtils::saveListingsForTimeslot($this);
    }

    /**
     * Returns the tracks etc. and their associated channels as planned for this show. Mainly used by NIPSWeb.
     */
    public function getShowPlan()
    {
        /*
         * Find out if there's a NIPSWeb Schema listing for this timeslot.
         * If not, throw back an empty array
         */
        $r = self::$db->query(
            'SELECT timeslot_item_id, channel_id FROM bapsplanner.timeslot_items
            WHERE timeslot_id=$1
            ORDER BY weight ASC',
            [$this->getID()]
        );

        if (!$r or self::$db->numRows($r) === 0) {
            //No show planned yet
            return [];
        } else {
            $tracks = [];
            foreach (self::$db->fetchAll($r) as $track) {
                $tracks[$track['channel_id']][] =
                    NIPSWeb_TimeslotItem::getInstance($track['timeslot_item_id'])->toDataSource();
            }

            return $tracks;
        }
    }

    /**
     * Get information about the Users signed into this Timeslot.
     *
     * @todo Cache this data?
     */
    public function getSigninInfo()
    {
        $result = self::$db->fetchAll(
            'SELECT * FROM (
                SELECT creditid AS memberid
                FROM schedule.show_credit WHERE show_id IN (
                    SELECT show_id FROM schedule.show_season
                    WHERE show_season_id IN (
                        SELECT show_season_id FROM schedule.show_season_timeslot
                        WHERE show_season_timeslot_id=$1
                    )
                )
                AND effective_from <= NOW()
                AND (effective_to IS NULL OR effective_to > NOW())
            ) AS t1
            LEFT JOIN (
                SELECT memberid, signerid FROM sis2.member_signin
                WHERE show_season_timeslot_id=$1
            ) AS t2 USING (memberid)',
            [$this->getID()]
        );

        return array_map(
            function ($x) {
                return [
                    'user' => MyRadio_User::getInstance($x['memberid']),
                    'signedby' => $x['signerid'] ? MyRadio_User::getInstance($x['signerid']) : null,
                ];
            },
            $result
        );
    }

    public function getMessages($offset = 0)
    {
        $result = self::$db->fetchAll(
            'SELECT c.commid AS id,
            commtypeid AS type,
            EXTRACT (EPOCH FROM date) AS time,
            subject AS title,
            content AS body,
            (statusid = 2) AS read,
            comm_source AS source
            FROM sis2.messages c
            INNER JOIN schedule.show_season_timeslot ts ON (c.timeslotid = ts.show_season_timeslot_id)
            WHERE  statusid <= 2 AND c.timeslotid = $1
            AND c.commid > $2
            ORDER BY c.commid ASC',
            [$this->getID(), $offset]
        );

        foreach ($result as $k => $v) {
            $result[$k]['read'] = ($v['read'] === 't');
            $result[$k]['time'] = intval($v['time']);
            $result[$k]['id'] = intval($v['id']);
            //Add the IP metadata
            if ($v['type'] == 3) {
                $result[$k]['location'] = SIS_Utils::ipLookup($v['source']);
            }
            $result[$k]['title'] = htmlspecialchars($v['title']);
            $result[$k]['body'] = htmlspecialchars($v['body']);
        }

        return $result;
    }

    /**
     * Sends a message to the timeslot for display in SIS.
     *
     * @param string $message the message to be sent
     * @return MyRadio_Timeslot
     */
    public function sendMessage($message)
    {
        $message = trim($message);

        if (empty($message)) {
            throw new MyRadioException('Message is empty.', 400);
        }

        $junk = SIS_Utils::checkMessageSpam($message);
        $warning = SIS_Utils::checkMessageSocialEngineering($message);

        if ($warning !== false) {
            $prefix = '<p class="bg-danger">' . $warning . '</p> ';
        } else {
            $prefix = '';
        }

        $source = $_SERVER['REMOTE_ADDR'];

        self::$db->query(
            'INSERT INTO sis2.messages (timeslotid, commtypeid, sender, subject, content, statusid, comm_source)
            VALUES ($1, $2, $3, $4, $5, $6, $7)',
            [
                $this->getID(),           // timeslot
                3,                        // commtypeid : website
                'MyRadio',                // sender
                substr($message, 0, 144), // subject : trancated message
                $prefix . $message,         // content : message with prefix
                $junk ? 4 : 1,            // statusid : junk or unread
                $source,                  // comm_source : IP
            ]
        );

        return $this;
    }

    /**
     * Signs the given user into the timeslot to say they were
     * on air at this time, if they haven't been signed in already.
     *
     * @param MyRadio_User $member
     */
    public function signIn(MyRadio_User $member)
    {
        // If member already signed in for whatever reason, don't bother trying again.
        $signedIn = !empty(self::$db->fetchOne(
            'SELECT * FROM sis2.member_signin
               WHERE show_season_timeslot_id=$1 AND memberid=$2',
            [$this->getID(), $member->getID()]
        ));
        if (!$signedIn) {
            self::$db->query(
                'INSERT INTO sis2.member_signin (show_season_timeslot_id, memberid, signerid)
                VALUES ($1, $2, $3)',
                [$this->getID(), $member->getID(), MyRadio_User::getInstance()->getID()]
            );
        }
    }

    public static function getCancelForm()
    {
        return (
        new MyRadioForm(
            'sched_cancel',
            'Scheduler',
            'cancelEpisode',
            [
                'debug' => false,
                'title' => 'Cancel Episode',
            ]
        )
        )->addField(
            new MyRadioFormField(
                'reason',
                MyRadioFormField::TYPE_BLOCKTEXT,
                ['label' => 'Please explain why this Episode should be removed from the Schedule']
            )
        )->addField(
            new MyRadioFormField(
                'show_season_timeslot_id',
                MyRadioFormField::TYPE_HIDDEN,
                ['value' => $_REQUEST['show_season_timeslot_id']]
            )
        );
    }

    public function getMoveForm()
    {
        $title = $this->getMeta('title') . ' - ' . CoreUtils::happyTime($this->getStartTime());
        return (
        new MyRadioForm(
            'sched_move',
            'Scheduler',
            'moveEpisode',
            [
                'debug' => false,
                'title' => 'Move Episode',
                'subtitle' => "Moving $title"
            ]
        ))->addField(new MyRadioFormField(
            'grp_info',
            MyRadioFormField::TYPE_SECTION,
            [
                    'label' => 'New Time',
                    'explanation' => 'Enter the new time to move the episode to. Take care with the end time.'
                ]
        ))->addField(new MyRadioFormField(
            'new_start_time',
            MyRadioFormField::TYPE_DATETIME,
            [
                    'label' => 'New Start Time',
                    'value' => date('d/m/Y H:i', $this->getStartTime())
            ]
        ))->addField(new MyRadioFormField(
            'new_end_time',
            MyRadioFormField::TYPE_DATETIME,
            [
            'label' => 'New End Time',
            'value' => date('d/m/Y H:i', $this->getEndTime())
            ]
        ))->addField(new MyRadioFormField(
            'grp_info_close',
            MyRadioFormField::TYPE_SECTION_CLOSE,
            []
        ))->addField(new MyRadioFormField(
            'show_season_timeslot_id',
            MyRadioFormField::TYPE_HIDDEN,
            ['value' => $this->getID()]
        ));
    }
}
