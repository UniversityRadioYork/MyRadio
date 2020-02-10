<?php


namespace MyRadio\Notifications;

use MyRadio\Config;
use MyRadio\ServiceAPI\MyRadio_User;

class MyRadio_TrainingLeftTraineeNotification extends MyRadio_Notification implements MyRadio_EmailNotification
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
     * @param string $sessionTime
     */
    public function __construct(MyRadio_User $trainee, string $sessionTime)
    {
        $this->trainee = $trainee;
        $this->sessionTime = $sessionTime;
    }

    public function toEmailBody(MyRadio_User $user): string
    {
        $name = $this->trainee->getFName();
        $time = $this->sessionTime;
        $stationName = Config::$long_name;

        return <<<EOF
Hi $name,

This is to confirm that you have left the training session at $time.

If you did this by mistake, don't worry - simply rejoin the training session in MyRadio.
 
Thanks!
$stationName Training
EOF;
    }

    public function getEmailSubject(MyRadio_User $user): string
    {
        return 'Left training';
    }
}
