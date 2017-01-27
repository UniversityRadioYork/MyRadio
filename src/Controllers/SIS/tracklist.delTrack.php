<?php
/**
 * Tracklist Track Deleter for SIS.
 */
use \MyRadio\SIS\SIS_Tracklist;

SIS_Tracklist::markTrackDeleted($_REQUEST['id']);
header('HTTP/1.1 204 No Content');
