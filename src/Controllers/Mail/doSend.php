<?php
/**
 * Send an email to a mailing list
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130526
 * @package MyURY_Mail
 */

//The Form definition
require 'Models/Mail/sendfrm.php';
$info = $form->readValues();

MyURYEmail::sendEmailToList(MyURY_List::getInstance($info['list']), $info['subject'], $info['body'], User::getInstance());

header('Location: '.$_SERVER['HTTP_REFERER'].'?message='.base64_encode('Message sent!'));