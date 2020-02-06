<?php


namespace MyRadio\Notifications;


use MyRadio\Config;
use MyRadio\ServiceAPI\MyRadio_User;

class MyRadio_TrainingJoinedTraineeNotification
extends MyRadio_Notification
implements MyRadio_EmailNotification
{
    /**
     * @var MyRadio_User
     */
    private $trainee;
    /**
     * @var MyRadio_User
     */
    private $trainer;

    /**
     * @var string
     */
    private $sessionTime;

    /**
     * MyRadio_TrainingJoinedTraineeNotification constructor.
     * @param MyRadio_User $trainee
     * @param MyRadio_User $trainer
     * @param string $sessionTime
     */
    public function __construct(MyRadio_User $trainee, MyRadio_User $trainer, string $sessionTime)
    {
        $this->trainee = $trainee;
        $this->trainer = $trainer;
        $this->sessionTime = $sessionTime;
    }

    public function toEmailBody(MyRadio_User $user): string
    {
        $name = $this->trainee->getName();
        $trainerName = $this->trainer->getName();
        $trainerFName = $this->trainer->getFName();
        $time = $this->sessionTime;
        $stationName = Config::$long_name;

        return <<<EOF
Hi $name,

Thanks for joining a training session at $time. You will be trained by $trainerName.

Just head over to the station at Vanbrugh College just before your slot and $trainerFName will be waiting for you!

If you realise you can't make it, please leave the session on MyRadio so $trainerFName doesn't have to wait for you.

See you on air soon!
$stationName Training
EOF;
    }

    public function getEmailSubject(MyRadio_User $user): string
    {
        return 'Attending training';
    }
}