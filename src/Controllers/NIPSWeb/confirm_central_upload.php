<?php
/**
 * Saves a cached upload into the URY Central Database
 *
 * @package MyRadio_NIPSWeb
 */

use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\ServiceAPI\MyRadio_Track;

$data = MyRadio_Track::identifyAndStoreTrack(
    $_REQUEST['fileid'],
    $_REQUEST['title'],
    $_REQUEST['artist'],
    $_REQUEST['album'],
    $_REQUEST['position'],
    isset($_REQUEST['explicit']) ? true : null
);
$data['fileid'] = $_REQUEST['fileid'];

echo CoreUtils::dataToJSON($data);
