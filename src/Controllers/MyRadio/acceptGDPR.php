<?php

use \MyRadio\MyRadio\URLUtils;
use \MyRadio\ServiceAPI\MyRadio_User;

$user = MyRadio_User::getInstance();
$user->setGDPRAccepted();
URLUtils::redirect('MyRadio');
