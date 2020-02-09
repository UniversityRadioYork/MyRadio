<?php


namespace MyRadio\Notifications;


use MyRadio\Config;
use MyRadio\ServiceAPI\MyRadio_Show;
use MyRadio\ServiceAPI\MyRadio_User;

class MyRadio_SeasonScheduledNotification
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
        return <<<EOF
Hi #NAME,

Congratulations! A new season of your show, $showName, has been scheduled at the following times:

$times

Remember that except in exceptional circumstances, you must give at least 48 hours notice for cancelling your show as part of your presenter contract.

If you do not do this for two shows in one season, all other shows are forfeit and may be cancelled.

You can cancel an episode by opening MyRadio and going to My Shows, clicking on the seasons number, then the episodes number, and clicking the cancel icon on the episode you want to cancel.

If you have any questions, contact the Programme Controller at pc@$email.

$sname Scheduling
EOF;

    }

    public function getEmailSubject(MyRadio_User $user): string
    {
        $showName = $this->show->getMeta('title');
        return "$showName - New Season Scheduled!";
    }
}