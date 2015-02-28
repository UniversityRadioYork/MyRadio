<?php
/**
 * Allows URY Trainers to create demo slots for new members to attend
 *
 * @package MyRadio_Scheduler
 */

use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\ServiceAPI\MyRadio_Demo;

$result = MyRadio_Demo::attend($_REQUEST['demoid']);
CoreUtils::redirect($module, 'listDemos', ['msg'=>$result]);
