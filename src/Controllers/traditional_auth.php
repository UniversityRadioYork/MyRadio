<?php

/**
 * Provides basic login requirement functionality to other PHP web systems,
 * backwards compatible with the old Shibbobleh system URY use to use.
 */
//Load the basic MyRadio framework

use \MyRadio\MyRadio\URLUtils;
use MyRadio\Config;
use MyRadio\ServiceAPI\MyRadio_APIKey;
use MyRadio\ServiceAPI\MyRadio_Swagger2;

require_once __DIR__.'/root_cli.php';

if (defined('SHIBBOBLEH_ALLOW_API') && SHIBBOBLEH_ALLOW_API === true
    && (!isset($_SESSION['memberid']))) {
    $caller = MyRadio_Swagger2::getAPICaller();
    if ($caller instanceof MyRadio_APIKey) {
        $_SESSION['memberid'] = Config::$system_user;
    }
}

//Check the current authentication status of the user
if ((!isset($_SESSION['memberid']) or $_SESSION['auth_use_locked'])
    && (!defined('SHIBBOBLEH_ALLOW_READONLY') or SHIBBOBLEH_ALLOW_READONLY === false)
) {
    //Authentication is required.
    header('HTTP/1.1 403 Forbidden');
    URLUtils::redirect('MyRadio', 'login', ['next' => $_SERVER['REQUEST_URI']]);
    exit;
}

//Check if the current app needs a timeslot selected
if ((!isset($_SESSION['timeslotid']) or $_SESSION['timeslotid'] === null)
    && (defined('SHIBBOBLEH_REQUIRE_TIMESLOT') and SHIBBOBLEH_REQUIRE_TIMESLOT)
) {
    //Timeslot needs configuring
    header('HTTP/1.1 403 Forbidden');
    URLUtils::redirect('MyRadio', 'timeslot', ['next' => $_SERVER['REQUEST_URI']]);
    exit;
}
