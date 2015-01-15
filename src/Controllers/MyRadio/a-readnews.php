<?php
/**
 * This is pretty much what every Controller should look like.
 * Some might include more than one model etc....
 *
 * @todo proper documentation
 *
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130718
 * @package MyRadio_Core
 */

use \MyRadio\MyRadio\MyRadioNews;
use \MyRadio\MyRadio\URLUtils;
use \MyRadio\ServiceAPI\MyRadio_User;

MyRadioNews::markNewsAsRead((int) $_REQUEST['newsentryid'], MyRadio_User::getInstance());
URLUtils::nocontent();
