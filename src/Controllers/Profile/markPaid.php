<?php
/**
* Adds a User payment
*
* @author Sam Willcocks <samw@ury.org.uk>
* @version 20140129
* @package MyRadio_Profile
*/
$user = MyRadio_User::getInstance($_REQUEST['memberid']);
$user->setPayment(Config::$membership_fee);

CoreUtils::backWithMessage('Payment data updated');
