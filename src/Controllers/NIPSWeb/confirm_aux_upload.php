<?php

/**
 * Confirms upload of a ManagedItem
 * 
 * @author Andy Durant <aj@ury.org.uk>
 * @version 20130509
 * @package MyRadio_NIPSWeb
 */

if (!isset($_REQUEST['fileid']) or !isset($_REQUEST['title']) or !isset($_REQUEST['expires']) or !isset($_REQUEST['auxid'])) {
  header('HTTP/1.1 400 Bad Request');
  exit;
}

$data = NIPSWeb_ManagedItem::storeItem($_REQUEST['fileid'], $_REQUEST['title']);
$data['fileid'] = $_REQUEST['fileid'];

require 'Views/MyRadio/datatojson.php';