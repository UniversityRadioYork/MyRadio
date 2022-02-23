<?php

use \MyRadio\MyRadio\URLUtils;
use \MyRadio\ServiceAPI\MyRadio_Demo;

$result = MyRadio_Demo::getInstance($_REQUEST['demoid'])->leave();
URLUtils::redirect($module, 'listDemos', ['msg' => $result === 0 ? -1 : $result * -1]); // -1 means left... see listDemos.php
