<?php
/**
 * Comet Server Handler for SIS
 *
 * @author  Andy Durant <aj@ury.org.uk>
 * @version 20131101
 * @package MyRadio_SIS
 */

use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\SIS\SIS_Utils;

//Allow Session writing from other requests
$session = $_SESSION;
session_write_close();

$pollFuncs = SIS_Utils::readPolls(array_merge(SIS_Utils::getPlugins(), SIS_Utils::getTabs()));

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
    $count++;
} while (empty($data) && $count < 50);

//Return the response data
CoreUtils::dataToJSON($data);
