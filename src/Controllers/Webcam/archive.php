<?php
/**
 * Controller for viewing webcam archives
*
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 21112012
 * @package MyRadio_Webcam
 */

use \MyRadio\ServiceAPI\MyRadio_Webcam;

$streams = MyRadio_Webcam::getStreams();
//Skip "Live"
/**
 * @todo This is quite a nasty way of doing it. Is there a better one?
 */
array_shift($streams);

$times = MyRadio_Webcam::getArchiveTimeRange();
