<?php
/**
 * Loads a NIPSWeb Auxillary playlist.
 */
use \MyRadio\MyRadio\URLUtils;
use \MyRadio\NIPSWeb\NIPSWeb_ManagedPlaylist;
use \MyRadio\NIPSWeb\NIPSWeb_ManagedUserPlaylist;

header("Access-Control-Allow-Origin: " . $_SERVER["HTTP_ORIGIN"]);
header("Access-Control-Allow-Credentials: true");

if (preg_match('/^aux-.*$/', $_REQUEST['libraryid']) === 1) {
    $libraryid = str_replace('aux-', '', $_REQUEST['libraryid']);
    $data = NIPSWeb_ManagedPlaylist::getInstance($libraryid)->getItems();
} else {
    $libraryid = str_replace('user-', '', $_REQUEST['libraryid']);
    $data = NIPSWeb_ManagedUserPlaylist::getInstance($libraryid)->getItems();
}

URLUtils::dataToJSON($data);
