<?php
/**
 * Controller for the focus Webcam Module. It's pretty much some webcams.
 * 
 * @author Andy Durant <aj@ury.org.uk>
 * @version 02082012
 * @package MyURY_Webcam
 */
$streams = MyURY_Webcam::getStreams();
$live = array_shift($streams);
require 'Views/MyURY/Webcam/streams.php';