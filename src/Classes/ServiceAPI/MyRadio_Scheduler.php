<?php
/**
 * This file provides the Scheduler class for MyRadio.
 */
namespace MyRadio\ServiceAPI;

use MyRadio\Config;
use MyRadio\MyRadioException;
use MyRadio\MyRadio\CoreUtils;
use MyRadio\MyRadio\MyRadioForm;
use MyRadio\MyRadio\MyRadioFormField;
use MyRadio\ServiceAPI\MyRadio_Term;

/**
 * Abstractor for the Scheduler Module.
 *
 * @uses    \Database
 *
 * @todo    Dedicated Term class
 */
class MyRadio_Scheduler extends ServiceAPI
{
    /**
     * This provides a temporary cache of the result from pendingAllocationsQuery.
     *
     * @var array
     */
    private static $pendingAllocationsResult = null;

    /**
     * Returns an Array of pending Season allocations.
     *
     * @return array An Array of MyRadio_Season objects which do not have an allocated timeslot,
     *               ordered by time submitted
     *
     * @todo Move to MyRadio_Season?
     */
    private static function pendingAllocationsQuery()
    {
        if (self::$pendingAllocationsResult === null) {
            /*
             * Must not be null - otherwise it hasn't been submitted yet
             */
            $result = self::$db->fetchColumn(
                'SELECT show_season_id FROM schedule.show_season
                WHERE show_season_id NOT IN (SELECT show_season_id FROM schedule.show_season_timeslot)
                AND submitted IS NOT NULL
                ORDER BY submitted ASC'
            );

            self::$pendingAllocationsResult = [];
            foreach ($result as $application) {
                self::$pendingAllocationsResult[] = MyRadio_Season::getInstance($application);
            }
        }

        return self::$pendingAllocationsResult;
    }

    /**
     * Returns the number of seasons awaiting a timeslot allocation.
     *
     * @return int the number of pending season allocations
     */
    public static function countPendingAllocations()
    {
        return sizeof(self::pendingAllocationsQuery());
    }

    /**
     * Returns all show requests awaiting a timeslot allocation.
     *
     * @return Array[MyRadio_Season] An array of Seasons of pending allocation
     */
    public static function getPendingAllocations()
    {
        return self::pendingAllocationsQuery();
    }

    /**
     * Return the number of show application disputes pending response from Master of Scheduling.
     *
     * @todo implement this
     *
     * @return int Zero.
     */
    public static function countPendingDisputes()
    {
        return 0;
    }


    /**
     * Returns if we are currently in term time.
     *
     * @return Boolean
     */
    public static function isTerm()
    {
        return MyRadio_Term::isTerm();
    }


    /**
     * Returns a list of show locations, organised so they can be used as a SELECT MyRadioFormField data source.
     */
    public static function getLocations()
    {
        self::wakeup();

        return self::$db->fetchAll(
            'SELECT location_id AS value, location_name AS text
                FROM schedule.location
                ORDER BY location_name ASC'
        );
    }

    /**
     * Returns a list of potential genres, organised so they can be used as a SELECT MyRadioFormField data source.
     */
    public static function getGenres()
    {
        self::wakeup();

        return self::$db->fetchAll('SELECT genre_id AS value, name AS text FROM schedule.genre ORDER BY name ASC');
    }

    /**
     * Returns a list of potential credit types, organsed so they can be used as a SELECT MyRadioFormField data source.
     */
    public static function getCreditTypes()
    {
        self::wakeup();

        return self::$db->fetchAll(
            'SELECT credit_type_id AS value, name AS text, is_in_byline
            FROM people.credit_type ORDER BY name ASC'
        );
    }

    /**
     * Returns an Array of Shows matching the given partial title.
     *
     * @param string $title A partial or total title to search for
     * @param int    $limit The maximum number of shows to return
     *
     * @return array 2D with each first dimension an Array as follows:<br>
     *               title: The title of the show<br>
     *               show_id: The unique id of the show
     */
    public static function findShowByTitle($term, $limit)
    {
        self::initDB();

        return self::$db->fetchAll(
            'SELECT DISTINCT ON (schedule.show.show_id)
            schedule.show.show_id, metadata_value AS title
            FROM schedule.show, schedule.show_metadata
            WHERE schedule.show.show_id = schedule.show_metadata.show_id
            AND metadata_key_id IN (SELECT metadata_key_id FROM metadata.metadata_key WHERE name=\'title\')
            AND metadata_value ILIKE \'%\' || $1 || \'%\' LIMIT $2',
            [$term, $limit]
        );
    }

    /**
     * @todo This probably shouldn't implement ServiceAPI
     */
    public function getID()
    {
        return 0;
    }


    /**
     * @param int   $term_id The term to check for
     * @param array $time:
     *                       day: The day ID (0-6) to check for
     *                       start_time: The start time in seconds since midnight
     *                       duration: The duration in seconds
     *
     * Return: Array of conflicts with week # as key and show as value
     */
    public static function getScheduleConflicts($term_id, $time)
    {
        self::initDB();
        $conflicts = [];
        $start_day = (new MyRadio_Term($term_id))->getTermStartDate() + ($time['day'] * 86400);
        //Iterate over each week
        //Changes to 11 weeks for semesters
        for ($i = 1; $i <= 11; ++$i) {
            $day_start = $start_day + (($i - 1) * 7 * 86400);

            //Get the start time
            $gmt_start = $day_start + $time['start_time'];

            $dst_offset = timezone_offset_get(timezone_open(Config::$timezone), date_create('@'.$gmt_start));

            if ($dst_offset !== false) {
                $start = $gmt_start - $dst_offset;
            } else {
                $start = $gmt_start;
            }

            //Query for conflicts
            $r = self::getScheduleConflict($start, $start + $time['duration']);

            //If there's a conflict, log it
            if (!empty($r)) {
                $conflicts[$i] = $r['show_season_id'];
            }
        }

        return $conflicts;
    }

    /**
     * Returns a schedule conflict between the given times, if one exists.
     *
     * @param int $start Start time
     * @param int $end   End time
     *
     * @return array empty if no conflict, show information otherwise
     */
    public static function getScheduleConflict($start, $end)
    {
        $start = CoreUtils::getTimestamp($start);
        $end = CoreUtils::getTimestamp($end - 1);

        return self::$db->fetchOne(
            'SELECT show_season_timeslot_id,
            show_season_id, start_time, start_time+duration AS end_time,
            \'$1\' AS requested_start, \'$2\' AS requested_end
            FROM schedule.show_season_timeslot
            WHERE (start_time <= $1 AND start_time + duration > $1)
            OR (start_time > $1 AND start_time < $2)',
            [$start, $end]
        );
    }

}
