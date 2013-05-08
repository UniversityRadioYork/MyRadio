<?php
/**
 * Loads a NIPSWeb Auto playlist
 * 
 * @author Andy Durant <lpw@ury.org.uk>
 * @version 20130508
 * @package MyURY_NIPSWeb
 */

  $data = NIPSWeb_AutoPlaylist::getInstance($_REQUEST['playlistid'])->getItems();

require 'Views/MyURY/Core/datatojson.php';