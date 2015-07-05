<?php
/**
 * Allows URY Trainers to create demo slots for new members to attend
 *
 * @package MyRadio_Scheduler
 */

use \MyRadio\MyRadio\URLUtils;
use \MyRadio\ServiceAPI\MyRadio_Demo;

$result = MyRadio_Demo::attend($_REQUEST['demoid']);
URLUtils::redirect($module, 'listDemos', ['msg'=>$result]);
