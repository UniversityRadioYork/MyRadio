<?php
/**
 * Comet Server Handler for SIS.
 */
use \MyRadio\MyRadio\URLUtils;
use \MyRadio\SIS\SIS_Utils;
use \MyRadio\Config;

//Allow Session writing from other requests
$session = $_SESSION;
session_write_close();

// Session has ended up without a timeslot
// User has probably logged out (& in) on another tab, so ask them to pick another one (handled by JS)
if (!isset($session['timeslotid'])) {
    header('HTTP/1.1 400 Bad Request');
    exit;
}

$pollFuncs = SIS_Utils::readPolls(Config::$sis_modules);

//Enter an infinite loop calling these functions, and enjoy the ride
//Times out after 50 cycles to prevent infinites or something like that
$count = 0;
$data = [];
do {
    foreach ($pollFuncs as $function) {
        $temp = call_user_func($function, $session);
        if (!empty($temp)) {
            $data = array_merge($data, $temp);
        }
    }
    sleep(1);
    ++$count;
} while (empty($data) && $count < 50);

//Return the response data
URLUtils::dataToJSON($data);
