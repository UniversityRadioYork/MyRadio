<?php
/**
 * Opt out of a mailing list
 *
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130527
 * @package MyRadio_Mail
 */

use \MyRadio\MyRadioException;
use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\ServiceAPI\MyRadio_List;
use \MyRadio\ServiceAPI\MyRadio_User;

if (!isset($_REQUEST['list'])) {
    throw new MyRadioException('List ID was not provided!', 400);
}

if (isset($_REQUEST['memberid'])) {
    CoreUtils::requirePermission(AUTH_EDITANYPROFILE);
    $user = $_REQUEST['memberid'];
} else {
    $user = -1;
}

$list = MyRadio_List::getInstance($_REQUEST['list']);
if ($list->optout(MyRadio_User::getInstance($user))) {
    CoreUtils::backWithMessage('You are now opted-out of '.$list->getName().'.');
} else {
    CoreUtils::backWithMessage('You could not be opted-out at this time. You may already have opted-out.');
}
