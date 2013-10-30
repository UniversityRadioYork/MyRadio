<?php
/**
 * Uploads a ManagedItem
 * 
 * @author Andy Durant <aj@ury.org.uk>
 * @version 20130509
 * @package MyRadio_NIPSWeb
 */
$data = NIPSWeb_ManagedItem::cacheItem($_FILES['audio']['tmp_name']);

require 'Views/MyRadio/datatojson.php';