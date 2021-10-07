<?php
/**
 * Join a Training Waiting List
 */
use \MyRadio\MyRadio\URLUtils;
use \MyRadio\ServiceAPI\MyRadio_Demo;

MyRadio_Demo::joinWaitingList($_REQUEST["presenterstatusid"]);
URLUtils::redirect($module, 'listWaitingLists', ['msg' => 0]);
