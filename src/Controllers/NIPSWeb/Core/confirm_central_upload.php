<?php
/**
 * Saves a cached upload into the URY Central Database
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 18042013
 * @package MyURY_NIPSWeb
 */
$data = MyURY_Track::identifyAndStoreTrack($_REQUEST['fileid'], $_REQUEST['title'], $_REQUEST['artist']);
$data['fileid'] = $_REQUEST['fileid'];

require 'Views/MyURY/Core/datatojson.php';