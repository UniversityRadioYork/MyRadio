<?php
/**
 * Schedule Getter for SIS
 *
 * @author Andy Durant <aj@ury.org.uk>
 * @version 20131116
 * @package MyRadio_SIS
 */

use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\ServiceAPI\MyRadio_Timeslot;

$data = MyRadio_Timeslot::getCurrentAndNext(null, 10);

CoreUtils::dataToJSON($data);
