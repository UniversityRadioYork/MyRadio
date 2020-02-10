<?php


namespace MyRadio\Notifications;

use MyRadio\Config;
use MyRadio\ServiceAPI\MyRadio_Show;
use MyRadio\ServiceAPI\MyRadio_User;

class MyRadio_SeasonRejectedNotification extends MyRadio_Notification implements MyRadio_EmailNotification
{
    /**
     * @var MyRadio_Show
     */
    private $show;
    /**
     * @var string
     */
    private $reason;

    /**
     * @param MyRadio_Show $show
     * @param string $reason
     */
    public function __construct(MyRadio_Show $show, string $reason)
    {
        $this->show = $show;
        $this->reason = $reason;
    }

    public function toEmailBody(MyRadio_User $user): string
    {
        $sname = Config::$short_name; // Heredocs can't do static class variables
        $email = Config::$email_domain;
        $showName = $this->show->getMeta('title');
        $reason = $this->reason;
        $name = $user->getFName();
        $reasonStr = empty($reason) ? "." : <<<EOF
, for the following reason:

$reason
EOF;

        return <<<EOF
Hi $name,

Your application for a new season of $showName was rejected$reasonStr

You can reapply online at any time. For more information, contact the Programme Controller at pc@$email.

$sname Scheduling
EOF;
    }

    public function getEmailSubject(MyRadio_User $user): string
    {
        $showName = $this->show->getMeta('title');
        return "$showName - Season Application Rejected";
    }
}
