<?php
/**
 * Help Tab Hidder for SIS
 *
 * @author Andy Durant <aj@ury.org.uk>
 * @version 20131123
 * @package MyRadio_SIS
 */

SIS_Utils::hideHelpTab($_SESSION['memberid']);
header('HTTP/1.1 204 No Content');
