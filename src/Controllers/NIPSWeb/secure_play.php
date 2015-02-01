<?php
/**
 * Streams a central database track if a play token is available
 *
 * @author  Lloyd Wallis <lpw@ury.org.uk>
 * @version 17032013
 * @package MyRadio_NIPSWeb
 */

use \MyRadio\Config;
use \MyRadio\MyRadioException;
use \MyRadio\NIPSWeb\NIPSWeb_Token;
use \MyRadio\NIPSWeb\NIPSWeb_Views;

if (!isset($_REQUEST['trackid']) or !isset($_REQUEST['recordid'])) {
    throw new MyRadioException('Bad Request - trackid and recordid required.', 400);
}
$recordid = (int) $_REQUEST['recordid'];
$trackid = (int) $_REQUEST['trackid'];

if (NIPSWeb_Token::hasToken($trackid)) {
    //Yes, clear the current play session and read the track
    $path = Config::$music_central_db_path."/records/$recordid/$trackid";

    if (isset($_REQUEST['ogg'])) {
        $path .= '.ogg';
        NIPSWeb_Views::serveOGG($path);
    } else {
        $path .= '.mp3';
        NIPSWeb_Views::serveMP3($path);
    }
} else {
    throw new MyRadioException('Invalid Play Token!', 403);
}
