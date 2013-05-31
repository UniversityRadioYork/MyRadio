#!/usr/local/bin/php -q
<?php
/**
 * This is the Email Pipe Controller - it will read in emails piped to it and do MyURY things with them
 * 
 * - if it's sent to a certain mailing list, it'll put it in the archives
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130530
 * @package MyURY_Mail
 * @uses \Database
 * @uses \CoreUtils
 */

ini_set("log_errors", 1);
ini_set("error_log", "/tmp/php-mailparser-error.log");

//Set up environment
date_default_timezone_get('Europe/London');
ini_set('include_path', str_replace('Controllers', '', __DIR__) . ':' . ini_get('include_path'));
define('SHIBBOBLEH_ALLOW_READONLY', true);
require_once 'Classes/MyURY/CoreUtils.php';
require_once 'Classes/Config.php';
require_once 'Classes/MyURYEmail.php';
require 'Models/Core/api.php';

//Read in email
$fd = fopen('php://stdin', 'r');
$email = '';
while (!feof($fd)) {
    $email .= fread($fd, 1024);
}

preg_match_all('/(^|\s)From:(.*)/i', $email, $sender);
preg_match_all('/(^|\s)(To|CC):(.*)/i', $email, $recipients);

fclose($fd);

if (!isset($sender[2][0])) {
  $sender = null;
} else {
  if (strstr($sender[2][0],'<') !== false) {
    $addr = preg_replace('/.*<(.*)>.*/', '$1', $sender[2][0]);
  } else {
    $addr = trim($sender[2][0]);
  }
  $sender = User::findByEmail($addr);
}

foreach ($recipients[3] as $recipient) {
  if (strstr($recipient,'<') !== false) {
    $addr = preg_replace('/.*<(.*)>.*/', '$1', $recipient);
  } else {
    $addr = trim($recipient);
  }
  
  $list = MyURY_List::getByName(explode('@',$addr)[0]);
  if (empty($list)) exit(0);
  if ($list->getID() == 52 && $sender == null) return; //Prevent loops
  if ($list !== null) {
    $list->archiveMessage($sender, $email);
  }
}
