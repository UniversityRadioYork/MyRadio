<?php
/**
 * Gives a User a Training Status
 *
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130825
 * @package MyRadio_Profile
 */
MyRadio_UserTrainingStatus::create(
    MyRadio_TrainingStatus::getInstance($_POST['status_id']),
    MyRadio_User::getInstance($_POST['memberid'])
);

CoreUtils::backWithMessage('Training data updated');
