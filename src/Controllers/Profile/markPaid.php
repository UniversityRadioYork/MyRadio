<?php
/**
* Adds a User payment
*
* @package MyRadio_Profile
*/

use \MyRadio\Config;
use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\ServiceAPI\MyRadio_User;

$user = MyRadio_User::getInstance($_REQUEST['memberid']);
$user->setPayment($container['config']->membership_fee);

CoreUtils::backWithMessage('Payment data updated');
