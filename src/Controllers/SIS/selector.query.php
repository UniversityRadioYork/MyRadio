<?php
/**
 * Selector Query for SIS
 * 
 * @author Andy Durant <aj@ury.org.uk>
 * @version 20131026
 * @package MyRadio_SIS
 */

$data = MyRadio_Selector::getStatusAtTime(time());

require 'Views/MyRadio/datatojson.php';