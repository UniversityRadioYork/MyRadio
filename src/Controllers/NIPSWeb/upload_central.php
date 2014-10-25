<?php
/**
 * Caches an uploaded track and attempts to identify it
 *
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130517
 * @package MyRadio_NIPSWeb
 */

use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\ServiceAPI\MyRadio_Track;

$data = MyRadio_Track::cacheAndIdentifyUploadedTrack($_FILES['audio']['tmp_name']);

CoreUtils::dataToJSON($data);
