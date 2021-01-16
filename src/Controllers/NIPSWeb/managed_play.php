<?php
/**
 * Streams a managed database item (jingles, beds etc).
 */
use \MyRadio\MyRadioException;
use \MyRadio\MyRadio\AuthUtils;
use \MyRadio\NIPSWeb\NIPSWeb_Views;
use \MyRadio\NIPSWeb\NIPSWeb_ManagedItem;

header("Access-Control-Allow-Origin: " . $_SERVER["HTTP_ORIGIN"]);
header("Access-Control-Allow-Credentials: true");

if (AuthUtils::hasPermission(AUTH_USENIPSWEB) || AuthUtils::hasPermission(AUTH_DOWNLOAD_LIBRARY)) {
    if (!isset($_REQUEST['managedid'])) {
        throw new MyRadioException('Bad Request - managedid required.', 400);
    }
    $managedid = (int) $_REQUEST['managedid'];
    NIPSWeb_Views::serveMP3(NIPSWeb_ManagedItem::getInstance($managedid)->getPath());
} else {
    throw new MyRadioException('You do not have access to this endpoint.', 403);
}
