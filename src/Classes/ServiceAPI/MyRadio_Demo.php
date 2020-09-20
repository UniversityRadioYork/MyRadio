<?php
/**
 * This file provides the Demo class for MyRadio.
 */
namespace MyRadio\ServiceAPI;

use MyRadio\Config;
use MyRadio\MyRadioException;
use MyRadio\MyRadio\CoreUtils;
use MyRadio\MyRadio\MyRadioForm;
use MyRadio\MyRadio\MyRadioFormField;
use MyRadio\MyRadioEmail;

/**
 * Abstractor for the Demo utilities.
 *
 * @uses    \Database
 */
class MyRadio_Demo extends ServiceAPI
{
    public static function registerDemo($time, $link = null, $training_type)
    {
        self::initDB();
        date_default_timezone_set('UTC');

        self::$db->query(
            "INSERT INTO schedule.demo (presenterstatusid, demo_time, demo_link, memberid)
            VALUES ($1, $2, $3, $4)",
            [$training_type, CoreUtils::getTimestamp($time), $link, $_SESSION["memberid"]]
        );
        date_default_timezone_set(Config::$timezone);

        return true;
    }

    public static function getForm()
    {
        return (
            new MyRadioForm(
                'sched_demo',
                'Scheduler',
                'createDemo',
                [
                    'title' => 'Scheduler',
                    'subtitle' => 'Create Training Session',
                ]
            )
        )->addField(
            new MyRadioFormField(
                'demo-training-type',
                MyRadioFormField::TYPE_SELECT,
                ["label" => "Training Type",
                "options" => MyRadio_TrainingStatus::getOptionsToTrain(MyRadio_User::getCurrentUser())
                ]
            )
        )->addField(
            new MyRadioFormField(
                'demo-datetime',
                MyRadioFormField::TYPE_DATETIME,
                ['label' => 'Date and Time of the session']
            )
        )->addField(
            new MyRadioFormField(
                'demo-link',
                MyRadioFormField::TYPE_TEXT,
                ['label' => "Zoom/Google Meets Link (Optional)",
                "required" => false]
            )
        );
    }

    public static function isUserAttendingDemo($demoid, $userid)
    {
        $r = self::$db->fetchColumn(
            "SELECT (1) FROM schedule.demo_attendee
            WHERE demo_id = $1 AND memberid = $2",
            [$demoid, $userid]
        );
        return count($r) > 0;
    }

    public static function isSpaceOnDemo($demoid)
    {
        return self::attendingDemoCount($demoid) < 2;
    }

    // Grrr...this returns names, not users.
    public static function usersAttendingDemo($demoid)
    {
        // First, retrieve all the memberids attending this demo
        
        $r = self::$db->fetchColumn(
            "SELECT memberid FROM schedule.demo_attendee
            WHERE demo_id = $1",
            [$demoid]
        );

        if (empty($r)) {
            return 'Nobody';
        }
        $str = MyRadio_User::getInstance($r[0])->getName();
        if (isset($r[1])) {
            $str .= ', '.MyRadio_User::getInstance($r[1])->getName();
        }

        return $str;
    }

    // Lets fix the above with this...grrr....
    public static function myRadioUsersAttendingDemo($demoid){
        $r = self::$db->fetchColumn(
            "SELECT memberid FROM schedule.demo_attendee
            WHERE demo_id = $1",
            [$demoid]
        );
        $attendees = [];
        foreach($r as $attendee){
            $attendees[] = MyRadio_User::getInstance($attendee);
        }
        return $attendees;
    }

    public static function attendingDemoCount($demoid)
    {
        return count(self::$db->fetchColumn(
            "SELECT memberid FROM schedule.demo_attendee WHERE demo_id = $1", [$demoid]
        ));
    }

    /**
     * Gets a list of available demo slots in the future.
     */
    public static function listDemos()
    {
        self::initDB();
        
        $result = self::$db->fetchAll(
            "SELECT demo_id, demo_link, presenterstatusid, demo_time, memberid FROM schedule.demo
            WHERE demo_time > NOW() ORDER BY demo_time ASC"
        );

        //Add the credits for each member
        $demos = [];
        foreach ($result as $demo) {
            $demo['demo_time'] = date('d M H:i', strtotime($demo['demo_time']));
            $demo['memberid'] = MyRadio_User::getInstance($demo['memberid'])->getName();
            $demo['presenterstatusid'] = MyRadio_TrainingStatus::getInstance($demo['presenterstatusid'])->getTitle();
            $demos[] = $demo;
        }
        return $demos;
    }

    /**
     * The current user is marked as attending a demo
     * Return 0: Success
     * Return 1: Demo Full
     * Return 2: Already Attending a Demo.
     */
    public static function attend($demoid)
    {
        self::initDB();
        //Get # of attendees
        if (self::attendingDemoCount($demoid) >= 2) {
            return 1;
        }

        //Check they aren't already attending one in the next week
        if (count(self::$db->fetchColumn(
            "SELECT demoid FROM schedule.demo_attendee
            INNER JOIN schedule.demo USING (demo_id)
            WHERE schedule.demo_attendee.memberid = $1
            AND demo_time <= (NOW() + INTERVAL '1 week')",
            [$_SESSION['memberid']]
        )) !== 0) {
            return 2;
        }

        self::$db->query(
            "INSERT INTO schedule.demo_attendee
            (demo_id, memberid)
            VALUES ($1, $2)",
            [$demoid, $_SESSION['memberid']]
        );
        $time = self::getDemoTime($demoid);
        $user = self::getDemoer($demoid);
        $attendee = MyRadio_User::getInstance();
        $link = self::getLink($demoid);
        MyRadioEmail::sendEmailToUser(
            $user,
            'New Training Attendee',
            $attendee->getName() . ' has joined your session at ' . $time . '.'
            . ($link ? " The training session is at " . $link : "")
        );
        MyRadioEmail::sendEmailToUser(
            $attendee,
            'Attending Training',
            'Hi '
            .$attendee->getFName() . ",\r\n\r\n"
            ."Thanks for joining a training session at $time. You will be trained by "
            .$user->getName()
            . ($link?'. The training session will be available at ' . $link:'. Just head over to the station in Vanbrugh College just before your slot '
            .' and the trainer will be waiting for you.')
            ."\r\n\r\nSee you on air soon!\r\n"
            .Config::$long_name
            .' Training'
        );

        return 0;
    }

    /**
     * The current user is unmarked as attending a demo.
     */
    public static function leave($demoid)
    {
        self::initDB();

        self::$db->query(
            "DELETE FROM schedule.demo_attendee
            WHERE demo_id = $1 AND memberid = $2",
            [$demoid, $_SESSION['memberid']]
        );
        $time = self::getDemoTime($demoid);
        $user = self::getDemoer($demoid);
        $attendee = MyRadio_User::getInstance();
        MyRadioEmail::sendEmailToUser(
            $user,
            'Training Attendee Left',
            $attendee->getName() . ' has left your session at ' . $time . '.'
        );
        MyRadioEmail::sendEmailToUser(
            $attendee,
            'Training Cancellation',
            'Hi ' . $attendee->getFName() . ",\r\n\r\n"
            ."Just to confirm that you have left the training session at $time. If this was accidental, simply rejoin."
            ."\r\n\r\nThanks!\r\n"
            .Config::$long_name
            .' Training'
        );

        return 0;
    }

    public static function getDemoTime($demoid)
    {
        self::initDB();
        $r = self::$db->fetchOne(
            "SELECT demo_time FROM schedule.demo WHERE demo_id = $1",
            [$demoid]
        );
        return $r['demo_time'];
    }

    public static function getDemoer($demoid)
    {
        self::initDB();
        $r = self::$db->fetchColumn(
            'SELECT memberid FROM schedule.demo WHERE demo_id=$1',
            [$demoid]
        );
        return MyRadio_User::getInstance($r[0]);
    }

    public static function getLink($demoid){
        self::initDB();
        $r = self::$db->fetchOne(
            "SELECT link FROM schedule.demo WHERE demo_id = $1",
            [$demoid]
        );
        return $r['link'];
    }

    public static function getTrainingType($demoid){
        self::initDB();
        $r = self::$db->fetchOne(
            "SELECT presenterstatusid FROM schedule.demo WHERE demo_id = $1",
            [$demoid]
        );
        return MyRadio_TrainingStatus::getInstance($r['presenterstatusid']);
    }

    public static function markTrained($demoid){
        $attendees = self::myRadioUsersAttendingDemo($demoid);
        foreach ($attendees as $attendee){
            MyRadio_UserTrainingStatus::create(MyRadio_Demo::getTrainingType($demoid),
            $attendee,
            MyRadio_User::getInstance($_SESSION['memberid'])
        );
        }
    }

}
