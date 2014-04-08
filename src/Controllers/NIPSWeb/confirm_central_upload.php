<?php
/**
 * Saves a cached upload into the URY Central Database
 *
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 18042013
 * @package MyRadio_NIPSWeb
 */
$data = MyRadio_Track::identifyAndStoreTrack($_REQUEST['fileid'],
                                             $_REQUEST['title'],
                                             $_REQUEST['artist'],
                                             $_REQUEST['album'],
                                             $_REQUEST['position']);
$data['fileid'] = $_REQUEST['fileid'];

require 'Views/MyRadio/datatojson.php';
