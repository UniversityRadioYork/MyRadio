<?php
/**
 * Schedule Tab for SIS
 * 
 * @author Andy Durant <aj@ury.org.uk>
 * @version 20130925
 * @package MyRadio_SIS
 */

$moduleInfo = array(
'name' => 'schedule',
'title' => 'Schedule',
'enabled' => true,
'pollfunc' => 'SIS_Remote::query_schedule',
'help' => 'Schedule tab lets you see what\'s on for the rest of the day',
);