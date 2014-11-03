<?php
/**
 * Provides a JS file with configuration options useful to the client
 *
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130525
 * @package MyRadio_Core
 */

use \MyRadio\Config;

header('Content-Type: text/javascript');
header('Cache-Control: max-age=86400, must-revalidate');
header('Expires: ', date('r', time()+86400));
header('HTTP/1.1 200 OK');

echo 'window.mConfig='. json_encode(Config::getPublicConfig()).';';
