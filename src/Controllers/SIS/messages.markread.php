<?php
/**
 * Message Mark Reader for SIS.
 */
use \MyRadio\SIS\SIS_Messages;

SIS_Messages::setMessageStatus(intval($_GET['id']), SIS_Messages::MSG_STATUS_READ);
header('HTTP/1.1 204 No Content');
