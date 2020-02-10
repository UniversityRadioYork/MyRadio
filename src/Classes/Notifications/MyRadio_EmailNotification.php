<?php


namespace MyRadio\Notifications;

use MyRadio\ServiceAPI\MyRadio_User;

interface MyRadio_EmailNotification
{
    public function toEmailBody(MyRadio_User $user): string;
    public function getEmailSubject(MyRadio_User $user): string;
}
