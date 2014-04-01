#!/usr/local/bin/php -q
<?php
/**
 * This is the Email Pipe Controller - it will read in emails piped to it and do MyRadio things with them
 *
 * - if it's sent to a certain mailing list, it'll put it in the archives
 *
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130712
 * @package MyRadio_Mail
 * @uses \Database
 * @uses \CoreUtils
 */
define('SILENT_EXCEPTIONS', true);
ini_set("log_errors", 1);
ini_set("error_log", "/tmp/php-mailparser-error.log");
ini_set('display_errors', 'Off');

require_once __DIR__.'/cli_common.php';
ini_set('display_errors', 'Off');

set_exception_handler(
    function () {
        exit(0);
    }
); //We do not want bounce messages from this!

//Read in email
$fd = fopen('php://stdin', 'r');
$email = '';
while (!feof($fd)) {
    $email .= fread($fd, 1024);
}

preg_match_all('/(^|\s)From:(.*)/i', $email, $sender);
preg_match_all('/(^|\s)(To|CC):(.*)/i', $email, $recipients);
preg_match('/(^|\s)X\-Spam\-Status:(.*)/i', $email, $spam);
if (!empty($spam) && strtolower(trim($spam[2])) == 'yes') {
    //Don't archive spam.
    exit(0);
}

fclose($fd);

if (!isset($sender[2][0])) {
    $sender = null;
} else {
    if (strstr($sender[2][0], '<') !== false) {
        $addr = preg_replace('/.*<(.*)>.*/', '$1', $sender[2][0]);
    } else {
        $addr = trim($sender[2][0]);
    }
    $sender = MyRadio_User::findByEmail($addr);
}

foreach ($recipients[3] as $recipient) {
    if (strstr($recipient, '<') !== false) {
        $addr = preg_replace('/.*<(.*)>.*/', '$1', $recipient);
    } else {
        $addr = trim($recipient);
    }

    $list = MyRadio_List::getByName(explode('@', $addr)[0]);
    if (empty($list)) {
        exit(0);
    }
    if ($list->getID() == 52 && $sender == null) {
        continue; //Prevent loops
    }
    if ($list !== null) {
        try {
            $list->archiveMessage($sender, $email);
        } catch (MyRadioException $e) {
            //Yes, it failed, but we don't want bounce messages
            exit(0);
        }
    }
}
