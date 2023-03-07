<?php

use \MyRadio\Config;
use \MyRadio\MyRadio\CoreUtils;
use MyRadio\MyRadio\URLUtils;

CoreUtils::requireTimeslot();

$location = Config::$website_url . 'webstudio';
URLUtils::redirectURI($location);
