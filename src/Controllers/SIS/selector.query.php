<?php
/**
 * Selector Query for SIS
 * 
 * @author Andy Durant <aj@ury.org.uk>
 * @version 20131026
 * @package MyRadio_SIS
 */

$sel = new MyRadio_Selector();
$data = $sel->query();

require 'Views/MyRadio/datatojson.php';