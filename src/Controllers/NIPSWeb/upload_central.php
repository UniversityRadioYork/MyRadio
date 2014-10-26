<?php
/**
 * Caches an uploaded track and attempts to identify it
 *
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130517
 * @package MyRadio_NIPSWeb
 */

use \MyRadio\MyRadioException;
use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\ServiceAPI\MyRadio_Track;

if (empty($_FILES)) {
	throw new MyRadioException('Failed to receive uploaded files. Is your POST max size big enough?', 500);
}

if (isset($_FILES['audio']['error']) and $_FILES['audio']['error'] !== 0) {
	throw new MyRadioException('File upload failed with code ' . $_FILES['audio']['error'], 500);
}

$data = MyRadio_Track::cacheAndIdentifyUploadedTrack($_FILES['audio']['tmp_name']);

CoreUtils::dataToJSON($data);
