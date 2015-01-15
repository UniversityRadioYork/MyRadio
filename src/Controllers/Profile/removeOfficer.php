<?php
/**
* Stands down an Officer
*
* @package MyRadio_Profile
*/

use \MyRadio\MyRadio\URLUtils;
use \MyRadio\ServiceAPI\MyRadio_Officer;

$memberofficerid = $_REQUEST['memberofficerid'];

MyRadio_Officer::standDown($memberofficerid);

URLUtils::backWithMessage('Officeship Stood Down');
