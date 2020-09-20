<?php
/**
 * Allows URY Trainers to create demo slots for new members to attend.
 */
use \MyRadio\MyRadio\URLUtils;
use \MyRadio\ServiceAPI\MyRadio_Demo;

$result = MyRadio_Demo::getInstance($_REQUEST['demoid'])->attend();
URLUtils::redirect($module, 'listDemos', ['msg' => $result]);
