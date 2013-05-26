<?php
/**
 * Send an email to a mailing list
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130526
 * @package MyURY_Mail
 */

if (!isset($_REQUEST['list'])) throw new MyURYException('List ID was not provided!', 400);

//The Form definition
require 'Models/Mail/sendfrm.php';
$form->setFieldValue('list', $_REQUEST['list'])
        ->setTemplate('Mail/send.twig')
        ->render(array('rcpt_str' => MyURY_List::getInstance($_REQUEST['list'])->getName()));