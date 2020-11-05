<?php
/**
 * Streams a central database track if a play token is available.
 */
use \MyRadio\MyRadio\AuthUtils;
use \MyRadio\Config;
use \MyRadio\MyRadioException;
use \MyRadio\NIPSWeb\NIPSWeb_Token;
use \MyRadio\NIPSWeb\NIPSWeb_Views;
use \MyRadio\ServiceAPI\MyRadio_Track;

header("Access-Control-Allow-Origin: " . $_SERVER["HTTP_ORIGIN"]);
header("Access-Control-Allow-Credentials: true");

if (
    AuthUtils::hasPermission(AUTH_USENIPSWEB) ||
    AuthUtils::hasPermission(AUTH_DOWNLOAD_LIBRARY)
) {
    if (!isset($_REQUEST['trackid'])) {
        throw new MyRadioException('Bad Request - trackid required.', 400);
    }

    $trackid = (int) $_REQUEST['trackid'];


    $track = MyRadio_Track::getInstance($trackid);

    if ($track) {

        $path = Config::$music_central_db_path."/records/".$track->getAlbum()->getID()."/".$trackid;

        if (isset($_REQUEST['ogg'])) {
            $path .= '.ogg';
            NIPSWeb_Views::serveOGG($path);
        } else {
            $path .= '.mp3';
            NIPSWeb_Views::serveMP3($path);
        }
    }
} else {
    throw new MyRadioException('You do not have access to this endpoint.', 403);
}
