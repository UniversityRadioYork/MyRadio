<?php

use MyRadio\ServiceAPI\MyRadio_Highlight;
use MyRadio\ServiceAPI\MyRadio_Timeslot;

$now = time();
$ts = MyRadio_Timeslot::getInstance($_SESSION['timeslotid']);
if (isset($_REQUEST['lastSegment'])) {
    $hl = MyRadio_Highlight::createFromLastSegment($ts->getID(), 10, $_REQUEST['notes'] ?? '');
} else {
    $duration = (int) ($_REQUEST['duration'] ?? 300);
    $hl = MyRadio_Highlight::create($ts->getID(), $now - $duration, $now, $_REQUEST['notes'] ?? '');
}
header('HTTP/1.1 204 No Content');
