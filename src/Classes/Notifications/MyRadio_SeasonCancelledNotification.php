<?php


namespace MyRadio\Notifications;


use MyRadio\Config;
use MyRadio\ServiceAPI\MyRadio_Show;
use MyRadio\ServiceAPI\MyRadio_User;

class MyRadio_SeasonCancelledNotification
extends MyRadio_Notification
implements MyRadio_EmailNotification
{
    /**
     * @var MyRadio_Show
     */
    private $show;
    /**
     * @var string
     */
    private $times;

    /**
     * @param MyRadio_Show $show
     * @param string $times
     */
    public function __construct(MyRadio_Show $show, string $times)
    {
        $this->show = $show;
        $this->times = $times;
    }

    public function toEmailBody(MyRadio_User $user): string
    {
        $sname = Config::$short_name; // Heredocs can't do static class variables
        $email = Config::$email_domain;
        $showName = $this->show->getMeta('title');
        $times = $this->times;
        $name = $user->getFName();
        return <<<EOF
Hello $name,

Please note that your show, $showName, has been cancelled for the rest of the current season.

This affects the following times:

$times

Please direct all enquiries to the Programme Controller at pc@$email.

$sname Scheduling
EOF;

    }

    public function getEmailSubject(MyRadio_User $user): string
    {
        $showName = $this->show->getMeta('title');
        return "$showName Cancelled";
    }
}