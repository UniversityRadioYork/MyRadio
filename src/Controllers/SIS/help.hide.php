<?php
/**
 * Help Tab Hidder for SIS.
 */
use \MyRadio\SIS\SIS_Utils;

SIS_Utils::hideHelpTab($_SESSION['memberid']);
header('HTTP/1.1 204 No Content');
