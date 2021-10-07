<?php
/**
 * Leave a Training Waiting List
 */
use \MyRadio\MyRadio\URLUtils;
use \MyRadio\ServiceAPI\MyRadio_Demo;

MyRadio_Demo::leaveWaitingList($_REQUEST["presenterstatusid"]);
URLUtils::redirect($module, 'listWaitingLists', ['msg' => 1]);
