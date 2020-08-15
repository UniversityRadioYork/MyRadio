<?php
/**
 * Streams a managed database track.
 */
use \MyRadio\MyRadioException;
use \MyRadio\NIPSWeb\NIPSWeb_Views;
use \MyRadio\NIPSWeb\NIPSWeb_ManagedItem;

header("Access-Control-Allow-Origin: " . $_SERVER["HTTP_ORIGIN"]);
header("Access-Control-Allow-Credentials: true");

if (!isset($_REQUEST['managedid'])) {
    throw new MyRadioException('Bad Request - managedid required.', 400);
}
$managedid = (int) $_REQUEST['managedid'];
NIPSWeb_Views::serveMP3(NIPSWeb_ManagedItem::getInstance($managedid)->getPath());
