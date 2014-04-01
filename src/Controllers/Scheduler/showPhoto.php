<?php
/**
 * Set the show photo
 *
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130529
 * @package MyRadio_Scheduler
 */

if (!isset($_REQUEST['show_id'])) {
    throw new MyRadioException('Show ID is required', 400);
}
$show = MyRadio_Show::getInstance($_REQUEST['show_id']);

//Require this is the user's show or the user can edit any show
if (!$show->isCurrentUserAnOwner()) {
    CoreUtils::requirePermission(AUTH_EDITSHOWS);
}

//The Form definition
require 'Models/Scheduler/showphotofrm.php';
$form->setFieldValue('show_id', $show->getID())->render();
