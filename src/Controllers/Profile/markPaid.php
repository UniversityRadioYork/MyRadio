<?php
/**
* Adds a User payment.
*/
use \MyRadio\Config;
use \MyRadio\MyRadio\URLUtils;
use \MyRadio\ServiceAPI\MyRadio_User;

$user = MyRadio_User::getInstance($_REQUEST['memberid']);
$user->setPayment(Config::$membership_fee);

URLUtils::backWithMessage('Payment data updated');
