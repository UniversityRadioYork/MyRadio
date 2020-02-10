<?php


namespace MyRadio\Notifications;

use MyRadio\Config;
use MyRadio\MyRadio\URLUtils;
use MyRadio\ServiceAPI\MyRadio_User;

class MyRadio_WelcomeEmailPasswordNotification extends MyRadio_Notification implements MyRadio_EmailNotification
{
    /**
     * @var string
     */
    private $username;
    /**
     * @var string
     */
    private $password;

    /**
     * MyRadio_WelcomeEmailPasswordNotification constructor.
     * @param MyRadio_User $user
     * @param string $username
     * @param string $password
     */
    public function __construct(string $username, string $password)
    {
        $this->username = $username;
        $this->password = $password;
    }

    public function toEmailBody(MyRadio_User $user): string
    {
        $stationName = Config::$long_name;
        $name = $user->getFName();
        $uname = $this->username;
        $pass = $this->password;
        $url = URLUtils::makeURL('MyRadio');
        return <<<EOF
Hi $name,

Welcome to $stationName! We're glad to have you here.

We've created an account for you on our back-end system, MyRadio. From there, you can book a training slot
and apply for your first show!

To access it, visit $url and use this login and password:
Username: $uname
Password: $pass

Please do not reply to this email. You will receive another email shortly, with information on how to get involved.

$stationName
EOF;
    }

    public function getEmailSubject(MyRadio_User $user): string
    {
        $stationName = Config::$long_name;
        return "Welcome to $stationName - Your Account";
    }
}
