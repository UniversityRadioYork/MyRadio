<?php
/**
 * Schedule Getter for SIS
 *
 * @package MyRadio_SIS
 */

use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\ServiceAPI\MyRadio_Timeslot;

$data = MyRadio_Timeslot::getCurrentAndNext(null, 10);

CoreUtils::dataToJSON($data);
