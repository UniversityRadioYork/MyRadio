#!/usr/local/bin/php -q
<?php
/**
 * DO NOT RUN THIS SCRIPT WILLY NILLY
 * this script will email literally everyone that has not logged into myradio in over a year. That is a lot of people.
 * after running this and waiting a couple weeks you should probably run the gdprdelete script
 * 
 * run with "php gdpremail.php"
 * 
 * Not actually tested for obvious reasons but probably works
 */

use \MyRadio\Database;
use \MyRadio\MyRadioException;
use \MyRadio\MyRadioError;
use \MyRadio\MyRadioEmail;
use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\ServiceAPI\MyRadio_User;

ini_set('log_errors', 1);
ini_set('error_log', '/tmp/php-gdpr-email.log');
ini_set('display_errors', 'On');

require_once '../src/Controllers/root_cli.php';

$db = Database::getInstance();

$warning_email = <<<EOT
<p>You are getting this email because you have not logged into MyRadio in over a year</p>
<p>In one week all of your personally identifiable data that is not required for webstudio or our public facing websites to function as advertised will be deleted.</p>
<p>If you wish to avoid this you can opt out of deletion by logging into your <a href="https://ury.org.uk/myradio">myradio account or by contact the ury computing team.</p>
<p>If you are happy for your personal data to be deleted feel free to ignore this eamil.</p>
--<br/>
The URY Computing team<br/>
<br/>
University Radio York 1350AM 88.3FM<br/>
---------------------------------------------<br/>
<a href="mailto:head.of.computing@ury.org.uk">head.of.computing@ury.org.uk</a><br/>
---------------------------------------------<br/>
On Air | Online | On Tap<br/>
<a href="https://ury.org.uk">ury.org.uk</a>
EOT;

$time = strtotime("-1 year", time());
$date = date("Y-m-d", $time);

echo "This script will Email all users that have not logged in for over a year\n Are you sure you want to continue? (y/n)";
$cmdinput = trim(fgets(STDIN));
if($cmdinput != 'Y'){
    return;
}
echo "Emailing users\n";

$memebersToEmail = $db->fetchAll(
    'SELECT memberid, last_login
	FROM public.member WHERE last_login <= $1 and joined <= $1',
    [$date]
);

$db->query(
    'UPDATE public.member
	SET data_removal=\'informed\'
	WHERE data_removal=\'default\' and last_login <= $1 and joined <= $1',
    [$date]
);

$db->query(
    'UPDATE public.member
	SET data_removal=\'informed\'
	WHERE data_removal=\'default\' and last_login IS NULL',
    []
);

foreach($memebersToEmail as $member){
    MyRadioEmail::sendEmailToUser(
        $member["memberid"],
        'MyRadio account deletion',
        $warning_email
    );
}
?>