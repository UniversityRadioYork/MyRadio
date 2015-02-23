<?php

/**
 * Ajax handler for Timelord
 *
 * @author  Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130905
 * @package MyRadio_Timelord
 */

use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\ServiceAPI\MyRadio_Selector;
use \MyRadio\ServiceAPI\MyRadio_Timeslot;
use \MyRadio\MyRadio\MyRadioNews;

$sel = new MyRadio_Selector();
$data = [
    'selector' => $sel->query(),
    'shows' => MyRadio_Timeslot::getCurrentAndNext(null, 2),
    'breaking' => MyRadioNews::getLatestNewsItem(3),
    'ob' => MyRadio_Selector::remoteStreams(),
    'silence' => $sel->isSilence(),
    'obit' => $sel->isObitHappening()
];

CoreUtils::dataToJSON($data);
