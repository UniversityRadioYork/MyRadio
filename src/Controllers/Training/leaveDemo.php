<?php

use \MyRadio\MyRadio\URLUtils;
use \MyRadio\ServiceAPI\MyRadio_Demo;

MyRadio_Demo::getInstance($_REQUEST['demoid'])->leave();
URLUtils::redirect($module, 'listDemos', ['msg' => 3]); // 3 means left... see listDemos.php
