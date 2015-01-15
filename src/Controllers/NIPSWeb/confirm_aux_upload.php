<?php

/**
 * Confirms upload of a ManagedItem
 *
 * @author Andy Durant <aj@ury.org.uk>
 * @version 20130509
 * @package MyRadio_NIPSWeb
 */

use \MyRadio\MyRadio\URLUtils;
use \MyRadio\NIPSWeb\NIPSWeb_ManagedItem;

if (!isset($_REQUEST['fileid'])
    or !isset($_REQUEST['title'])
    or !isset($_REQUEST['expires'])
    or !isset($_REQUEST['auxid'])
) {
    header('HTTP/1.1 400 Bad Request');
    exit;
}

$data = NIPSWeb_ManagedItem::storeItem($_REQUEST['fileid'], $_REQUEST['title']);
$data['fileid'] = $_REQUEST['fileid'];

URLUtils::dataToJSON($data);
