<?php
/**
 * Loads a NIPSWeb Auxillary playlist
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 06042013
 * @package MyURY_NIPSWeb
 */

if (is_numeric($_REQUEST['libraryid'])) {
  $data = NIPSWeb_ManagedPlaylist::getInstance($_REQUEST['libraryid'])->getItems();
} else {
  $data = NIPSWeb_ManagedUserPlaylist::getInstance($_REQUEST['libraryid'])->getItems();
}

require 'Views/MyURY/Core/datatojson.php';