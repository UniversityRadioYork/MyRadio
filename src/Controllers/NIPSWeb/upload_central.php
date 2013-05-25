<?php
/**
 * Caches an uploaded track and attempts to identify it
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130517
 * @package MyURY_NIPSWeb
 */
$data = MyURY_Track::cacheAndIdentifyUploadedTrack($_FILES['audio']['tmp_name']);

require 'Views/MyURY/datatojson.php';