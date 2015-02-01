<?php
/**
 * Gives a User a Training Status
 *
 * @package MyRadio_Profile
 */

use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\ServiceAPI\MyRadio_User;
use \MyRadio\ServiceAPI\MyRadio_TrainingStatus;
use \MyRadio\ServiceAPI\MyRadio_UserTrainingStatus;

MyRadio_UserTrainingStatus::create(
    MyRadio_TrainingStatus::getInstance($_POST['status_id']),
    MyRadio_User::getInstance($_POST['memberid'])
);

CoreUtils::backWithMessage('Training data updated');
