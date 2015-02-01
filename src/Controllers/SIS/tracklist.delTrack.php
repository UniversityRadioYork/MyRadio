<?php
/**
 * Tracklist Track Deleter for SIS
 *
 * @package MyRadio_SIS
 */

use \MyRadio\SIS\SIS_Tracklist;

SIS_Tracklist::markTrackDeleted($_GET['id']);
header('HTTP/1.1 204 No Content');
