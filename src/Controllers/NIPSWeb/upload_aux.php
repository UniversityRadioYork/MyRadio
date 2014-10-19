<?php
/**
 * Uploads a ManagedItem
 *
 * @author Andy Durant <aj@ury.org.uk>
 * @version 20130509
 * @package MyRadio_NIPSWeb
 */

use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\NIPSWeb\NIPSWeb_ManagedItem;

$data = NIPSWeb_ManagedItem::cacheItem($_FILES['audio']['tmp_name']);

CoreUtils::dataToJSON($data);
