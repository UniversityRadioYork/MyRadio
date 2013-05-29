<?php
/**
 * Set the show photo
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130529
 * @package MyURY_Scheduler
 */

//The Form definition
require 'Models/Scheduler/showphotofrm.php';
$form->render();

$data = $form->readValues();

$show = MyURY_Show::getInstance($data['show_id']);
//Require this is the user's show or the user can edit any show
if (!$show->isCurrentUserAnOwner()) {
  CoreUtils::requirePermission(AUTH_EDITSHOWS);
}

$show->setShowPhoto($data['image_file']['tmp_name']);