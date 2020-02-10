<?php


namespace MyRadio\Notifications;

use MyRadio\Config;
use MyRadio\ServiceAPI\MyRadio_Officer;
use MyRadio\ServiceAPI\MyRadio_User;

class MyRadio_WelcomeEmailNotification extends MyRadio_Notification implements MyRadio_EmailNotification
{
    public function toEmailBody(MyRadio_User $user): string
    {
        $name = $user->getFName();
        $sm = MyRadio_Officer::getOfficerByAlias('station.manager')->getCurrentHolders()[0];
        $smFname = $sm->getFName();
        $smName = $sm->getName();

        return str_replace(
            ['#SM_FNAME', '#SM_NAME', '#NAME'],
            [$smFname, $smName, $name],
            Config::$welcome_email
        );
    }

    public function getEmailSubject(MyRadio_User $user): string
    {
        $stationName = Config::$long_name;
        return "Welcome to $stationName!";
    }
}
