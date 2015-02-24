<?php
/**
 * Loads a NIPSWeb Auto playlist
 *
 * @package MyRadio_NIPSWeb
 */

use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\NIPSWeb\NIPSWeb_AutoPlaylist;

 $playlistid = str_replace('auto-', '', $_REQUEST['playlistid']);

 $data = NIPSWeb_AutoPlaylist::getInstance($playlistid)->getTracks();

CoreUtils::dataToJSON($data);
