<?php
/**
 * This is pretty much what every Controller should look like.
 * Some might include more than one model etc....
 *
 * @todo    Proper Documentation
 */
use \MyRadio\Config;
use \MyRadio\MyRadio\URLUtils;
use \MyRadio\ServiceAPI\Artist;

if (!isset($_REQUEST['term'])) {
    $data = [];
} else {
    $data = Artist::findByName(
        $_REQUEST['term'],
        isset($_REQUEST['limit']) ? intval($_REQUEST['limit']) : Config::$ajax_limit_default
    );
}
URLUtils::dataToJSON($data);
