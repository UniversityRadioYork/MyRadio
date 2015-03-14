<?php
/**
 * This is pretty much what every Controller should look like.
 * Some might include more than one model etc....
 *
 * @todo    Proper Documentation
 * @package MyRadio_Core
 */

use \MyRadio\Config;
use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\ServiceAPI\MyRadio_Album;

if (isset($_REQUEST['id'])) {
    $data = MyRadio_Album::getInstance($_REQUEST['id']);
} elseif (!isset($_REQUEST['term'])) {
    $data = [];
} else {
    $data = MyRadio_Album::findByName(
        $_REQUEST['term'],
        isset($_REQUEST['limit']) ? intval($_REQUEST['limit']) : $container['config']->ajax_limit_default
    );
}
CoreUtils::dataToJSON($data);
