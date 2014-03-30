<?php
/**
 * Message Mark Reader for SIS
 *
 * @author Andy Durant <aj@ury.org.uk>
 * @version 20131101
 * @package MyRadio_SIS
 */

SIS_Messages::setMessageStatus(intval($_GET['id']), SIS_Messages::MSG_STATUS_READ);
header('HTTP/1.1 204 No Content');
