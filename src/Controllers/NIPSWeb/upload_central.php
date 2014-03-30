<?php
/**
 * Caches an uploaded track and attempts to identify it
 *
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130517
 * @package MyRadio_NIPSWeb
 */
$data = MyRadio_Track::cacheAndIdentifyUploadedTrack($_FILES['audio']['tmp_name']);

require 'Views/MyRadio/datatojson.php';
