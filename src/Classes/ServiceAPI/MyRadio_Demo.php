<?php
/**
 * This file provides the Demo class for MyRadio
 * @package MyRadio_Demo
 */

/**
 * Abstractor for the Demo utilities
 *
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130607
 * @package MyRadio_Demo
 * @uses \Database
 */
class MyRadio_Demo extends MyRadio_Metadata_Common
{
    public static function registerDemo($time)
    {
        self::initDB();
        date_default_timezone_set('UTC');

        /**
         * Check for conflicts
         */
        $r = MyRadio_Scheduler::getScheduleConflict($time, $time+3600);
        if (!empty($r)) {
            //There's a conflict
            throw new MyRadioException('There is already something scheduled at that time', MyRadioException::FATAL);

            return false;
        }

        /**
         * Demos use the timeslot member as the credit for simplicity
         */
        self::$db->query(
            'INSERT INTO schedule.show_season_timeslot (show_season_id, start_time, memberid, approvedid, duration)
            VALUES (0, $1, $2, $2, \'01:00:00\')',
            array(CoreUtils::getTimestamp($time), $_SESSION['memberid'])
        );
        date_default_timezone_set(Config::$timezone);

        return true;
    }

    public static function attendingDemo($demoid)
    {
        if (MyRadio_User::getInstance()->hasAuth(AUTH_ADDDEMOS)) {
            $r = self::$db->fetch_column('SELECT creditid FROM schedule.show_credit WHERE show_id = 0 AND effective_from=$1 AND credit_type_id=7', array(self::getDemoTime($demoid)));
            if (empty($r)) {
                return 'Nobody';
            }
            $str = MyRadio_User::getInstance($r[0])->getName();
            if (isset($r[1])) {
                $str .= ', '.MyRadio_User::getInstance($r[1])->getName();
            }

            return $str;
        } else {
            if (self::attendingDemoCount($demoid) < 2) {
                return 'Space Available!';
            } else {
                return 'Full';
            }
        }
    }

    public static function attendingDemoCount($demoid)
    {
        return self::$db->num_rows(self::$db->query('SELECT creditid FROM schedule.show_credit WHERE show_id = 0 AND effective_from=$1 AND credit_type_id=7', array(self::getDemoTime($demoid))));
    }

    /**
     * Gets a list of available demo slots in the future
     */
    public static function listDemos()
    {
        self::initDB();
        $result = self::$db->fetch_all(
            'SELECT show_season_timeslot_id, start_time, memberid FROM schedule.show_season_timeslot
            WHERE show_season_id = 0 AND start_time > NOW() ORDER BY start_time ASC'
        );

        //Add the credits for each member
        $demos = array();
        foreach ($result as $demo) {
            $demo['start_time'] = date('d M H:i', strtotime($demo['start_time']));
            $demo['memberid'] = MyRadio_User::getInstance($demo['memberid'])->getName();
            $demos[] = array_merge($demo, array('attending' => self::attendingDemo($demo['show_season_timeslot_id'])));
        }

        return $demos;
    }

    /**
     * The current user is marked as attending a demo
     * Return 0: Success
     * Return 1: Demo Full
     * Return 2: Already Attending a Demo
     */
    public static function attend($demoid)
    {
        self::initDB();
        //Get # of attendees
        if (self::attendingDemoCount($demoid) >= 2) {
            return 1;
        }

        //Check they aren't already attending one in the next week
        if (self::$db->num_rows(
            self::$db->query(
                'SELECT creditid FROM schedule.show_credit WHERE show_id=0 AND creditid=$1
                AND effective_from >= NOW() AND effective_from <= (NOW() + INTERVAL \'1 week\') LIMIT 1',
                array($_SESSION['memberid'])
            )
        ) === 1
        ) {
            return 2;
        }

        self::$db->query(
            'INSERT INTO schedule.show_credit (show_id, credit_type_id, creditid, effective_from, effective_to, memberid, approvedid)
            VALUES (0, 7, $1, $2, $2, $1, $1)',
            array($_SESSION['memberid'], self::getDemoTime($demoid))
        );
        $time = self::getDemoTime($demoid);
        $user = self::getDemoer($demoid);
        $attendee = MyRadio_User::getInstance();
        MyRadioEmail::sendEmailToUser($user, 'New Demo Attendee', $attendee->getName().' has joined your demo at '.$time.'.');
        MyRadioEmail::sendEmailToUser(
            $attendee,
            'Attending Demo',
            'Hi '
            .$attendee->getFName()
            .",\r\n\r\nThanks for joining a demo at $time. You will be demoed by "
            .$user->getName()
            .'. Just head over to the station in Vanbrugh College just before your demo and the trainer will be waiting for you.'
            ."\r\n\r\nSee you on air soon!\r\n"
            .Config::$long_name
            ." Training"
        );

        return 0;
    }

    public static function getDemoTime($demoid)
    {
        self::initDB();
        $r = self::$db->fetch_column('SELECT start_time FROM schedule.show_season_timeslot WHERE show_season_timeslot_id=$1', array($demoid));

        return $r[0];
    }

    public static function getDemoer($demoid)
    {
        self::initDB();
        $r = self::$db->fetch_column('SELECT memberid FROM schedule.show_season_timeslot WHERE show_season_timeslot_id=$1', array($demoid));

        return MyRadio_User::getInstance($r[0]);
    }
}
