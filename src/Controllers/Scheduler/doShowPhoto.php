<?php
/**
 * Set the show photo
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130529
 * @package MyURY_Scheduler
 */

//Require this is the user's show or the user can edit any show
if (!$show->isCurrentUserAnOwner()) {
  CoreUtils::requirePermission(AUTH_EDITSHOW);
}

//The Form definition
require 'Models/Scheduler/showphotofrm.php';
$form->render();

$data = $form->readValues();
print_r($data);