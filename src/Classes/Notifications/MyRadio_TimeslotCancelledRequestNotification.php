<?php


namespace MyRadio\Notifications;

use MyRadio\Config;
use MyRadio\MyRadio\CoreUtils;
use MyRadio\MyRadio\URLUtils;
use MyRadio\ServiceAPI\MyRadio_Show;
use MyRadio\ServiceAPI\MyRadio_Timeslot;
use MyRadio\ServiceAPI\MyRadio_User;

class MyRadio_TimeslotCancelledRequestNotification extends MyRadio_Notification implements MyRadio_EmailNotification
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
        $showName = $this->timeslot->getMeta('title');
        $time = CoreUtils::happyTime($this->timeslot->getStartTime());
        $reason = $this->reason;
        $url = URLUtils::makeURL(
            'Scheduler',
            'cancelEpisode',
            ['show_season_timeslot_id' => $this->timeslot->getID(), 'reason' => base64_encode($reason)]
        );
        $reasonStr = empty($reason) ? '.' : <<<EOF
, for the following reason:

$reason
EOF;

        return <<<EOF
Hi,

A presenter of $showName has requested to cancel an episode at $time$reasonStr

Due to the short notice, it has been passed to you for consideration.

To cancel the episode, visit $url.

$sname Scheduling
EOF;
    }

    public function getEmailSubject(MyRadio_User $user): string
    {
        $showName = $this->timeslot->getMeta('title');
        return "$showName Episode Cancellation Request";
    }
}
