<?php
/**
 * Tracklist Track Deleter for SIS
 * 
 * @author Andy Durant <aj@ury.org.uk>
 * @version 20131101
 * @package MyRadio_SIS
 */

SIS_Tracklist::markTrackDeleted($_GET['id']);
header('HTTP/1.1 204 No Content');