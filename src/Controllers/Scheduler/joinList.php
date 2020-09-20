<?php
/**
 * Join a Training Waiting List
 */
use \MyRadio\MyRadio\URLUtils;
use \MyRadio\ServiceAPI\MyRadio_Demo;

MyRadio_Demo::joinWaitingList($_SESSION["presenterstatusid"]);
URLUtils::redirect($module, 'listDemos', ['msg' => 0]);
