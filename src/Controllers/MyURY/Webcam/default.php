<?php
/**
 * The default Controller for the Webcam Module. It's pretty much some webcams.
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 28072012
 * @package MyURY_Webcam
 */
$streams = MyURY_Webcam::getStreams();
require 'Views/MyURY/Webcam/streams.php';