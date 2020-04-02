<?php

use \MyRadio\Config;
use \MyRadio\MyRadio\CoreUtils;

CoreUtils::requireTimeslot();

$location = Config::$website_url . 'webstudio?timeslot_id=' . $_SESSION['timeslotid'];

header("HTTP/1.1 302 Found");
header("Location: $location");
