<?php
/**
* Stands down an Officer
*
* @package MyRadio_Profile
*/

use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\ServiceAPI\MyRadio_Officer;

$memberofficerid = $_REQUEST['memberofficerid']

MyRadio_Officer::standDown($memberofficerid);

CoreUtils::backWithMessage('Officeship Stood Down');
