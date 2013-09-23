<?php
/**
 * Webcam Plugin for SIS
 * 
 * @author Andy Durant <aj@ury.org.uk>
 * @version 20130923
 * @package MyURY_SIS
 */

$name = 'webcam';
$title = 'Webcam Selector';
$enabled = true;
$startOpen = false;
$help = 'You may have noticed that Studio 1 now has two webcams. The Webcam section over to the left lets you choose which of the station\'s cameras can be seen by listeners.'

$required_permission = AUTH_MODIFYWEBCAM;
$required_location = true;

$template = 'SIS/plugins/webcam.twig'