<?php

use \MyRadio\MyRadio\URLUtils;
use \MyRadio\ServiceAPI\MyRadio_Demo;

MyRadio_Demo::markTrained($_REQUEST['demoid']);
URLUtils::redirect($module, 'listDemos', ['msg' => 4]); // 4 means finished and trained... see listDemos.php
