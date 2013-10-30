<?php
/**
 * Loads a NIPSWeb Auxillary playlist
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @author Andy Durant <aj@ury.org.uk>
 * @version 20130512
 * @package MyRadio_NIPSWeb
 */

if (preg_match('/^aux-.*$/', $_REQUEST['libraryid']) === 1) {
  $libraryid = str_replace('aux-','',$_REQUEST['libraryid']);
  $data = NIPSWeb_ManagedPlaylist::getInstance($libraryid)->getItems();
} 
else {
  $libraryid = str_replace('user-','',$_REQUEST['libraryid']);
  $data = NIPSWeb_ManagedUserPlaylist::getInstance($libraryid)->getItems();
}

require 'Views/MyRadio/datatojson.php';