<?php

/**
 * This file provides the Demo class for MyRadio.
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
 * Abstractor for the Demo utilities.
 *
 * @uses    \Database
 */
class MyRadio_Demo extends ServiceAPI
{

    private $demo_id;
    private $demo_time;
    private $demo_link;
    private $presenterstatusid;
    private $memberid;
    private $demo_max_participants;

    protected function __construct($demoid)
    {
        $this->demo_id =  (int) $demoid;

        self::initDB();

        $result = self::$db->fetchOne(
            "SELECT * FROM schedule.demo WHERE demo_id = $1",
            [$demoid]
        );

        if (empty($result)) {
            throw new MyRadioException("The specified demo " . $demoid . "doesn't exist.");
            return;
        }

        $this->demo_time = $result['demo_time'];
        $this->demo_link = $result['demo_link'];
        $this->demo_max_participants = $result['max_participants'];
        $this->presenterstatusid = $result['presenterstatusid'];
        $this->memberid = $result["memberid"];
    }

    public function getID()
    {
        return $this->demo_id;
    }

    public static function registerDemo($time, $training_type, $max_participants, $link = null)
    {
        if ($time == null || $training_type == null || !is_numeric($time)) {
            throw new MyRadioException("A training demo must have a time and training date.", 400);
        } else {
            try {
                $training = MyRadio_TrainingStatus::getInstance($training_type);

                self::initDB();

                self::$db->query(
                    "INSERT INTO schedule.demo (presenterstatusid, demo_time, demo_link, max_participants, memberid)
            VALUES ($1, $2, $3, $4, $5)",
                    [$training_type, CoreUtils::getTimestamp($time), $link, $max_participants ,$_SESSION["memberid"]]
                );
                date_default_timezone_set(Config::$timezone);

                // Let people waiting for this know
                $waiters = self::trainingWaitingList($training_type);
                foreach ($waiters as $waiter) {
                    $user = MyRadio_User::getInstance($waiter['memberid']);
                    MyRadioEmail::sendEmailToUser(
                        $user,
                        "Available Training Session",
                        "Hi " . $user->getFName() . ","
                            . "\r\n\r\n A session to get you " . $training->getTitle()
                            . " has opened up. Check it out on MyRadio.\r\n\r\n"
                            . URLUtils::makeURL('Training', 'listDemos') . "\r\n\r\n"
                            . Config::$long_name . " Training"
                    );
                }
            } catch (MyRadioException $e) {
                throw $e;
            }
        }
        return true;
    }

    public function editDemo($time, $training_type, $max_participants, $link = null)
    {

        // TODO, Only edit your training demos, or any if you have perms
        // TODO: Allow changing demoer

        if ($time == null || $training_type == null || $max_participants == null) {
            throw new MyRadioException(
                "A training demo must have a time, training date and maximum number of participants.",
                400);
        } else {
            if ($time != $this->demo_time || $link != $this->demo_link || $training_type != $this->presenterstatusid) {
                // Do the Update
                self::$db->query(
                    "UPDATE schedule.demo SET demo_time = $1, demo_link = $2, presenterstatusid = $3,
                         max_participants = $4
                WHERE demo_id = $5",
                    [CoreUtils::getTimestamp($time), $link, $training_type, $max_participants, $this->getID()]
                );
                // Email People
                $attendees = $this->myRadioUsersAttendingDemo();
                $attendees[] = $this->getDemoer();
                
                // Work out what to tell people re. where their training is
                if ($link == null && $this->demo_link != null) {
                    $demo_location = "It is now in person at our studios in Vanbrugh College.";
                } elseif ($link != null && $this->demo_link == null) {
                    $demo_location = "It is now online and will be hosted at " . $link;
                } elseif ($link != null) {
                    $demo_location = "It will now be at " . $link;
                } else {
                    $demo_location = "";
                }

                foreach ($attendees as $attendee) {
                    MyRadioEmail::sendEmailToUser(
                        $attendee,
                        "Updated Training Session",
                        "Hi " . $attendee->getFName()
                            . "\r\n\r\n There's been a change to your training session on " . $this->demo_time
                            . ".\r\n\r\n"
                            . ($time != $this->demo_time ? "It is now at "
                                . CoreUtils::happyTime($time) . ".\r\n\r\n" : "")
                            . $demo_location . "\r\n\r\n"
                            . Config::$long_name . " Training Team"
                    );
                }
                $this->demo_link = $link;
                $this->demo_time = CoreUtils::getTimestamp($time);
                $this->presenterstatusid = $training_type;
            }
        }
    }

    public static function getForm()
    {
        return (new MyRadioForm(
            'sched_demo',
            'Training',
            'createDemo',
            [
                'title' => 'Training',
                'subtitle' => 'Create Training Session',
            ]
        ))->addField(
            new MyRadioFormField(
                'demo_training_type',
                MyRadioFormField::TYPE_SELECT,
                [
                    "label" => "Training Type",
                    "options" => MyRadio_TrainingStatus::getOptionsToTrain(MyRadio_User::getCurrentUser())
                ]
            )
        )->addField(
            new MyRadioFormField(
                'demo_datetime',
                MyRadioFormField::TYPE_DATETIME,
                ['label' => 'Date and Time of the session']
            )
        )->addField(
            new MyRadioFormField(
                'demo_link',
                MyRadioFormField::TYPE_TEXT,
                [
                    'label' => "Zoom/Google Meets Link (Optional)",
                    "required" => false
                ]
            )
        )->addField(
            new MyRadioFormField(
                'demo_max_participants',
                MyRadioFormField::TYPE_NUMBER,
                [
                    'label' => "Maximum Number of People who can sign up to training session",
                    'options' => [
                        'min' => 1,
                        'max' => 4,
                    ],
                    'value' => 2,
                    'required' => true
                ]
            )
        );
    }

    public function getEditForm()
    {
        return self::getForm()
            ->setSubtitle("Edit Training Session")
            ->editMode(
                $this->getID(),
                [
                    "demo_training_type" => $this->getTrainingType()->getID(),
                    "demo_datetime" => CoreUtils::happyTime($this->getDemoTime()),
                    "demo_link" => $this->getLink()
                ]
            );
    }

    public function isUserAttendingDemo($userid)
    {
        $r = self::$db->fetchColumn(
            "SELECT (1) FROM schedule.demo_attendee
            WHERE demo_id = $1 AND memberid = $2",
            [$this->demo_id, $userid]
        );
        return count($r) > 0;
    }

    public function isSpaceOnDemo(): bool
    {
        return $this->attendingDemoCount() < $this->demo_max_participants;
    }

    // Grrr...this returns names, not users.
    public function usersAttendingDemo()
    {
        // First, retrieve all the memberids attending this demo

        $r = self::$db->fetchColumn(
            "SELECT memberid FROM schedule.demo_attendee
            WHERE demo_id = $1",
            [$this->demo_id]
        );

        if (empty($r)) {
            return 'Nobody';
        }
        $str = MyRadio_User::getInstance($r[0])->getName();
        if (isset($r[1])) {
            $str .= ', ' . MyRadio_User::getInstance($r[1])->getName();
        }

        return $str;
    }

    // Lets fix the above with this...grrr....
    public function myRadioUsersAttendingDemo()
    {
        $r = self::$db->fetchColumn(
            "SELECT memberid FROM schedule.demo_attendee
            WHERE demo_id = $1",
            [$this->demo_id]
        );
        $attendees = [];
        foreach ($r as $attendee) {
            $attendees[] = MyRadio_User::getInstance($attendee);
        }
        return $attendees;
    }

    public function attendingDemoCount()
    {
        return count(self::$db->fetchColumn(
            "SELECT memberid FROM schedule.demo_attendee WHERE demo_id = $1",
            [$this->demo_id]
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
    public function attend()
    {
        //Get # of attendees
        if ($this->attendingDemoCount() >= 2) {
            return 1;
        }

        //Check they aren't already attending one in the next week
        if (count(self::$db->fetchColumn(
            "SELECT demo_id FROM schedule.demo_attendee
            INNER JOIN schedule.demo USING (demo_id)
            WHERE schedule.demo_attendee.memberid = $1
            AND demo_time <= (NOW() + INTERVAL '1 week')
            AND presenterstatusid = $2",
            [$_SESSION['memberid'], $this->presenterstatusid]
        )) !== 0) {
            return 2;
        }

        self::$db->query(
            "INSERT INTO schedule.demo_attendee
            (demo_id, memberid)
            VALUES ($1, $2)",
            [$this->demo_id, $_SESSION['memberid']]
        );

        $user = $this->getDemoer();
        $attendee = MyRadio_User::getInstance();
        MyRadioEmail::sendEmailToUser(
            $user,
            'New Training Attendee',
            $attendee->getName() . ' has joined your session at ' . $this->getDemoTime() . '.'
                . ($this->getLink() ? " The training session is at " . $this->getLink() : "")
        );
        MyRadioEmail::sendEmailToUser(
            $attendee,
            'Attending Training',
            'Hi '
                . $attendee->getFName() . ",\r\n\r\n"
                . "Thanks for joining a training session at " . $this->getDemoTime() . ". You will be trained by "
                . $user->getName()
                . ($this->getLink() ? '. The training session will be available at '
                    . $this->getLink() : '. Just head over to the station in Vanbrugh College just before your slot '
                    . ' and the trainer will be waiting for you.')
                . "\r\n\r\nSee you on air soon!\r\n"
                . Config::$long_name
                . ' Training'
        );

        // Take off waiting list
        if (self::onWaitingList($this->presenterstatusid)) {
            self::leaveWaitingList($this->presenterstatusid);
        }

        return 0;
    }

    /**
     * The current user is unmarked as attending a demo.
     */
    public function leave()
    {

        self::$db->query(
            "DELETE FROM schedule.demo_attendee
            WHERE demo_id = $1 AND memberid = $2",
            [$this->demo_id, $_SESSION['memberid']]
        );

        $attendee = MyRadio_User::getInstance();
        MyRadioEmail::sendEmailToUser(
            $this->getDemoer(),
            'Training Attendee Left',
            $attendee->getName() . ' has left your session at ' . $this->getDemoTime() . '.'
        );
        MyRadioEmail::sendEmailToUser(
            $attendee,
            'Training Cancellation',
            'Hi ' . $attendee->getFName() . ",\r\n\r\n"
                . "Just to confirm that you have left the training session at " . $this->getDemoTime()
                . ". If this was accidental, simply rejoin. "
                . "Meanwhile, you can join the waiting list, and we'll let you know if a session becomes available. "
                . URLUtils::makeURL("Training", "listWaitingLists")
                . "\r\n\r\nThanks!\r\n"
                . Config::$long_name
                . ' Training'
        );


        return 0;
    }

    public function getDemoTime()
    {
        return $this->demo_time;
    }

    public function getDemoer()
    {
        return MyRadio_User::getInstance($this->memberid);
    }

    public function getLink()
    {
        return $this->demo_link;
    }

    public function getTrainingType()
    {
        return MyRadio_TrainingStatus::getInstance($this->presenterstatusid);
    }

    public function markTrained()
    {
        $attendees = $this->myRadioUsersAttendingDemo();
        foreach ($attendees as $attendee) {
            MyRadio_UserTrainingStatus::create(
                $this->getTrainingType(),
                $attendee,
                MyRadio_User::getInstance($_SESSION['memberid'])
            );
        }
    }

    public static function joinWaitingList($presenterstatusid)
    {
        self::initDB();

        $r = self::$db->fetchColumn(
            "SELECT memberid FROM schedule.demo_waiting_list
            WHERE memberid = $1 AND presenterstatusid = $2",
            [$_SESSION['memberid'], $presenterstatusid]
        );

        if (count($r) != 0) {
            // Already waiting for this training status
            return 1;
        } else {
            self::$db->query(
                "INSERT INTO schedule.demo_waiting_list (memberid, presenterstatusid, date_added)
                VALUES ($1, $2, NOW())",
                [$_SESSION['memberid'], $presenterstatusid]
            );
        }

        return 0;
    }

    public static function leaveWaitingList($presenterstatusid)
    {
        self::initDB();
        self::$db->query(
            "DELETE FROM schedule.demo_waiting_list
            WHERE memberid = $1
            AND presenterstatusid = $2",
            [$_SESSION['memberid'], $presenterstatusid]
        );
    }

    public static function userWaitingList()
    {
        self::initDB();
        return self::$db->fetchAll(
            "SELECT presenterstatusid, date_added FROM schedule.demo_waiting_list
            WHERE memberid = $1
            ORDER BY date_added ASC",
            [$_SESSION['memberid']]
        );
    }

    public static function trainingWaitingList($presenterstatusid)
    {
        self::initDB();
        return self::$db->fetchAll(
            "SELECT memberid, date_added FROM schedule.demo_waiting_list
            WHERE presenterstatusid = $1
            ORDER BY date_added ASC",
            [$presenterstatusid]
        );
    }

    public static function onWaitingList($presenterstatusid)
    {
        $list = self::userWaitingList();
        foreach ($list as $entry) {
            if ($entry['presenterstatusid'] == $presenterstatusid) {
                return true;
            }
        }
        return false;
    }
}
