<?php
/**
 * Gives a User a Training Status
 *
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130825
 * @package MyRadio_Profile
 */

use \MyRadio\MyRadio\URLUtils;
use \MyRadio\ServiceAPI\MyRadio_User;
use \MyRadio\ServiceAPI\MyRadio_TrainingStatus;
use \MyRadio\ServiceAPI\MyRadio_UserTrainingStatus;

MyRadio_UserTrainingStatus::create(
    MyRadio_TrainingStatus::getInstance($_POST['status_id']),
    MyRadio_User::getInstance($_POST['memberid'])
);

URLUtils::backWithMessage('Training data updated');
