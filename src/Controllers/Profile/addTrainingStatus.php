<?php
/**
 * Gives a User a Training Status
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130825
 * @package MyURY_Profile
 */
MyURY_UserTrainingStatus::create(
        MyURY_TrainingStatus::getInstance($_POST['status_id']), 
        User::getInstance($_POST['memberid']));

CoreUtils::backWithMessage('Training data updated');