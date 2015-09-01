<?php
/**
 * Uploads a ManagedItem
 *
 * @package MyRadio_NIPSWeb
 */

use \MyRadio\MyRadio\URLUtils;
use \MyRadio\NIPSWeb\NIPSWeb_ManagedItem;

$data = NIPSWeb_ManagedItem::cacheItem($_FILES['audio']['tmp_name']);

URLUtils::dataToJSON($data);
