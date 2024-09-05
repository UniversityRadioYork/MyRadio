#!/usr/local/bin/php -q
<?php
/**
 * DO NOT RUN THIS SCRIPT WILLY NILLY
 * This script will completely yoink a users personal details, show credits, podcast credits and a bunch of other stuff.
 * Only run this script if a user has explicity declared they wish to be forgotten and understand the consequences.
 * 
 * run with "php gdprdeleteuser.php [userid]"
 */

use \MyRadio\Database;
use \MyRadio\MyRadioException;
use \MyRadio\MyRadioError;
use \MyRadio\MyRadioEmail;
use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\ServiceAPI\MyRadio_User;

ini_set('log_errors', 1);
ini_set('error_log', '/tmp/php-gdpr-deleteuser.log');
ini_set('display_errors', 'On');

require_once '../src/Controllers/root_cli.php';

$db = Database::getInstance();

$time = strtotime("-1 year", time());
$date = date("Y-m-d", $time);

$deletedUserId = 1350883;

$userid = $argv[1];

echo "This script will delete all the users personal data and some database links to the user\n Are you sure you want to continue? (y/n)";
$cmdinput = trim(fgets(STDIN));
if($cmdinput != 'Y'){
    return;
}
echo "User selected for deletion: " . $userid . "\n";

try{
    $db->query(
        'INSERT INTO public.member(
            memberid, fname, nname, sname, college, receive_email, data_removal)
            VALUES ($1, \'deleted\', \'user\', 10, false, \'deleted\')',
        [$deletedUserId]
    );
}  catch (exception $e) {
    echo 'deleting user\n';
}

$db->query(
    'UPDATE schedule.show_credit SET memberid=$1 WHERE memberid=$2',
    [$deletedUserId,$userid]
);

$db->query(
    'UPDATE uryplayer.podcast_credit SET creditid=$1 WHERE creditid=$2',
    [$deletedUserId,$userid]
);

$db->query(
    'UPDATE bapsplanner.managed_items SET memberid=$1 WHERE memberid=$2',
    [$deletedUserId,$userid]
);

$db->query(
    'UPDATE schedule.timeslot_metadata SET memberid=$1 WHERE memberid=$2',
    [$deletedUserId,$userid]
);

$db->query(
    'UPDATE public.mail_alias_member SET memberid=$1 WHERE memberid=$2',
    [$deletedUserId,$userid]
);

$db->query(
    'UPDATE public.member_year SET memberid=$1 WHERE memberid=$2',
    [$deletedUserId,$userid]
);

$db->query(
    'UPDATE public.member_presenterstatus SET memberid=$1 WHERE memberid=$2',
    [$deletedUserId,$userid]
);

$db->query(
    'UPDATE public.member_pass SET memberid=$1 WHERE memberid=$2',
    [$deletedUserId,$userid]
);

$db->query(
    'UPDATE uryplayer.podcast_metadata SET memberid=$1 WHERE memberid=$2',
    [$deletedUserId,$userid]
);

$db->query(
    'UPDATE mail.email_recipient_member SET memberid=$1 WHERE memberid=$2',
    [$deletedUserId,$userid]
);

$db->query(
    'UPDATE uryplayer.podcast SET memberid=$1 WHERE memberid=$2',
    [$deletedUserId,$userid]
);

$db->query(
    'UPDATE public.mail_subscription SET memberid=$1 WHERE memberid=$2',
    [$deletedUserId,$userid]
);

$db->query(
    'UPDATE mail.alias_member SET destination=$1 WHERE destination=$2',
    [$deletedUserId,$userid]
);

$db->query(
    'UPDATE public.member
	SET college=10, phone=DEFAULT, email=DEFAULT, receive_email=false, local_name=DEFAULT, local_alias=DEFAULT, account_locked=true, last_login=DEFAULT, endofcourse=DEFAULT, eduroam=DEFAULT, usesmtppassword=DEFAULT, joined=DEFAULT, require_password_change=DEFAUlT, profile_photo=DEFAULT, bio=DEFAULT, auth_provider=DEFAULT, contract_signed=DEFAULT, gdpr_accepted=DEFAULT, wheelchair=DEFAULT, data_removal=\'deleted\'
	WHERE memberid=$1',
    [$deletedUserId,$userid]
);
?>