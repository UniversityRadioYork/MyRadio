<?php
/**
 * Reject a season application
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130728
 * @package MyRadio_Scheduler
 */

//Model: The Form definition
require 'Models/Scheduler/rejectfrm.php';
$data = $form->readValues();

MyRadio_Season::getInstance($data['season_id'])->reject($data['reason'], $data['notify_user']);

CoreUtils::redirect('Scheduler', 'default');
