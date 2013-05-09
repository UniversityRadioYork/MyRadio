<?php
/**
 * Loads a NIPSWeb Auto playlist
 * 
 * @author Andy Durant <lpw@ury.org.uk>
 * @version 20130508
 * @package MyURY_NIPSWeb
 */

 $playlistid = str_replace('auto-','',$_REQUEST['playlistid']);

 $data = NIPSWeb_AutoPlaylist::getInstance($playlistid)->getTracks();

require 'Views/MyURY/Core/datatojson.php';