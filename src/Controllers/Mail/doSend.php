<?php
/**
 * Send an email to a mailing list
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130526
 * @package MyRadio_Mail
 */

//The Form definition
require 'Models/Mail/sendfrm.php';
$info = $form->readValues();

MyRadioEmail::sendEmailToList(MyRadio_List::getInstance($info['list']), $info['subject'], $info['body'], User::getInstance());

CoreUtils::backWithMessage('Message sent!');