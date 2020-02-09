<?php


namespace MyRadio\Notifications;


use MyRadio\Config;
use MyRadio\MyRadio\CoreUtils;
use MyRadio\ServiceAPI\MyRadio_Show;
use MyRadio\ServiceAPI\MyRadio_Timeslot;
use MyRadio\ServiceAPI\MyRadio_User;

class MyRadio_TimeslotCancelledNotification
extends MyRadio_Notification
implements MyRadio_EmailNotification
{
    /**
     * @var MyRadio_Timeslot
     */
    private $timeslot;
    /**
     * @var string
     */
    private $reason;

    /**
     * @param MyRadio_Timeslot $timeslot
     * @param string $reason
     */
    public function __construct(MyRadio_Timeslot $timeslot, string $reason)
    {
        $this->timeslot = $timeslot;
        $this->reason = $reason;
    }

    public function toEmailBody(MyRadio_User $user): string
    {
        $sname = Config::$short_name; // Heredocs can't do static class variables
        $email = Config::$email_domain;
        $showName = $this->timeslot->getMeta('title');
        $time = CoreUtils::happyTime($this->timeslot->getStartTime());
        $reason = $this->reason;
        $reasonStr = empty($reason) ? '.' : <<<EOF
, for the following reason:

$reason
EOF;

        return <<<EOF
Hello #NAME,

Please note that your an episode of your show $showName at $time has been cancelled by the Programming Team$reasonStr

Please direct all enquiries to the Programme Controller at pc@$email.

$sname Scheduling
EOF;

    }

    public function getEmailSubject(MyRadio_User $user): string
    {
        $showName = $this->timeslot->getMeta('title');
        return "Episode of $showName Cancelled";
    }
}