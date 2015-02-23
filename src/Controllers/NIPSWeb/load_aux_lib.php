<?php
/**
 * Loads a NIPSWeb Auxillary playlist
 *
 * @author  Lloyd Wallis <lpw@ury.org.uk>
 * @author  Andy Durant <aj@ury.org.uk>
 * @version 20130512
 * @package MyRadio_NIPSWeb
 */

use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\NIPSWeb\NIPSWeb_ManagedPlaylist;
use \MyRadio\NIPSWeb\NIPSWeb_ManagedUserPlaylist;

if (preg_match('/^aux-.*$/', $_REQUEST['libraryid']) === 1) {
    $libraryid = str_replace('aux-', '', $_REQUEST['libraryid']);
    $data = NIPSWeb_ManagedPlaylist::getInstance($libraryid)->getItems();
} else {
    $libraryid = str_replace('user-', '', $_REQUEST['libraryid']);
    $data = NIPSWeb_ManagedUserPlaylist::getInstance($libraryid)->getItems();
}

CoreUtils::dataToJSON($data);
