<?php
/**
 * Schedule Getter for SIS
 * 
 * @author Andy Durant <aj@ury.org.uk>
 * @version 20131116
 * @package MyRadio_SIS
 */

$data = MyRadio_Timeslot::getCurrentAndNext(null, 10);

require 'Views/MyRadio/datatojson.php';