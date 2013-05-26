<?php
/**
 * Opt out of a mailing list
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130527
 * @package MyURY_Mail
 */

if (!isset($_REQUEST['list'])) throw new MyURYException('List ID was not provided!', 400);

if (isset($_REQUEST['memberid'])) {
  CoreUtils::requirePermission(AUTH_EDITANYPROFILE);
  $user = $_REQUEST['memberid'];
} else {
  $user = -1;
}

$list = MyURY_List::getInstance($_REQUEST['list']);
if ($list->optout(User::getInstance($user))) {
  CoreUtils::backWithMessage('You are now opted-out of '.$list->getName().'.');
} else {
  CoreUtils::backWithMessage('You could not be opted-out at this time. You may already have opted-out.');
}