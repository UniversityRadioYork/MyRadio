<?php
/**
 * Selector Query for SIS
 * 
 * @author Andy Durant <aj@ury.org.uk>
 * @version 20131026
 * @package MyURY_SIS
 */

$sel = new MyURY_Selector();
$data = $sel->query();

require 'Views/MyURY/datatojson.php';