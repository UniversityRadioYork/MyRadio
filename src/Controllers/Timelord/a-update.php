<?php

/**
 * Ajax handler for Timelord
 *
 * @package MyRadio_Timelord
 */

use \MyRadio\MyRadio\URLUtils;
use \MyRadio\ServiceAPI\MyRadio_Selector;
use \MyRadio\ServiceAPI\MyRadio_Timeslot;
use \MyRadio\MyRadio\MyRadioNews;

$data = [
    'selector' => MyRadio_Selector::setQuery(),
    'shows' => MyRadio_Timeslot::getCurrentAndNext(null, 2),
    'breaking' => MyRadioNews::getLatestNewsItem(3),
    'ob' => MyRadio_Selector::remoteStreams(),
    'silence' => MyRadio_Selector::isSilence(),
    'obit' => MyRadio_Selector::isObitHappening()
];

URLUtils::dataToJSON($data);
