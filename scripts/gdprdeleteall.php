#!/usr/local/bin/php -q
<?php
/**
 * DO NOT RUN THIS SCRIPT WILLY NILLY
 * this script will delete the personal data of any members that have been emailed regarding deletion and have not acted
 * you should probably run the gdpremail script first then wait a week or two
 * 
 * run with "php gdprdeleteall.php"
 */

use \MyRadio\Database;
use \MyRadio\MyRadioException;
use \MyRadio\MyRadioError;
use \MyRadio\MyRadioEmail;
use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\ServiceAPI\MyRadio_User;

ini_set('log_errors', 1);
ini_set('error_log', '/tmp/php-gdpr-deleteall.log');
ini_set('display_errors', 'On');

require_once '../src/Controllers/root_cli.php';

$db = Database::getInstance();

$time = strtotime("-1 year", time());
$date = date("Y-m-d", $time);

echo "This script will delete details of all users that have not logged in for over a year\n Are you sure you want to continue? (y/n)";
$cmdinput = trim(fgets(STDIN));
if($cmdinput != 'Y'){
    return;
}
echo "deleting user data\n";

$db->query(
    'UPDATE public.member
	SET data_removal=\'default\'
	WHERE data_removal=\'informed\' and last_login >= $1 ',
    [$date]
);

$db->query(
    'UPDATE public.member
	SET college=10, phone=DEFAULT, receive_email=false, endofcourse=DEFAULT,  wheelchair=DEFAULT, data_removal=\'deleted\'
	WHERE data_removal=\'informed\'',
    []
);
?>