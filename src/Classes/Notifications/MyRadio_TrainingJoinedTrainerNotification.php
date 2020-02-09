<?php


namespace MyRadio\Notifications;


use MyRadio\Config;
use MyRadio\ServiceAPI\MyRadio_User;

class MyRadio_TrainingJoinedTrainerNotification
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
        $trainerFName = $this->trainer->getFName();
        $time = $this->sessionTime;

        return <<<EOF
Hi $trainerFName,

$name has joined your training session at $time.
EOF;
    }

    public function getEmailSubject(MyRadio_User $user): string
    {
        return 'New Training Attendee';
    }
}