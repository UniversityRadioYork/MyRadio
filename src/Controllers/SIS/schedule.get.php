<?php
/**
 * Schedule Getter for SIS.
 */
use \MyRadio\MyRadio\URLUtils;
use \MyRadio\ServiceAPI\MyRadio_Timeslot;

$data = MyRadio_Timeslot::getCurrentAndNext(null, 10);

URLUtils::dataToJSON($data);
