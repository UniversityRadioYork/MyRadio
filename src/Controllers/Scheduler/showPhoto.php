<?php
/**
 * Set the show photo
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130529
 * @package MyURY_Scheduler
 */

if (!isset($_REQUEST['show_id'])) throw new MyURYException('Show ID is required', 400);
$show = MyURY_Show::getInstance($_REQUEST['show_id']);

//Require this is the user's show or the user can edit any show
if (!$show->isCurrentUserAnOwner()) {
  CoreUtils::requirePermission(AUTH_EDITSHOW);
}

//The Form definition
require 'Models/Scheduler/showphotofrm.php';
$form->render();