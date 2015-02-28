<?php
/**
 * This is pretty much what every Controller should look like.
 * Some might include more than one model etc....
 *
 * @todo proper documentation
 *
 * @package MyRadio_Core
 */

use \MyRadio\MyRadio\MyRadioNews;
use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\ServiceAPI\MyRadio_User;

MyRadioNews::markNewsAsRead((int) $_REQUEST['newsentryid'], MyRadio_User::getInstance());
CoreUtils::nocontent();
