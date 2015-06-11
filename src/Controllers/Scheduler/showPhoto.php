<?php
/**
 * Set the show photo
 *
 * @package MyRadio_Scheduler
 */

use \MyRadio\MyRadioException;
use \MyRadio\MyRadio\AuthUtils;
use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\ServiceAPI\MyRadio_Show;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //Submitted
    $data = MyRadio_Show::getPhotoForm()->readValues();

    $show = MyRadio_Show::getInstance($data['show_id']);
    //Require this is the user's show or the user can edit any show
    if (!$show->isCurrentUserAnOwner()) {
        AuthUtils::requirePermission(AUTH_EDITSHOWS);
    }

    $show->setShowPhoto($data['image_file']['tmp_name']);

    CoreUtils::backWithMessage("Show Photo Updated!");

} else {
    //Not Submitted

    if (!isset($_REQUEST['show_id'])) {
        throw new MyRadioException('Show ID is required', 400);
    }
    $show = MyRadio_Show::getInstance($_REQUEST['show_id']);

    //Require this is the user's show or the user can edit any show
    if (!$show->isCurrentUserAnOwner()) {
        AuthUtils::requirePermission(AUTH_EDITSHOWS);
    }

    MyRadio_Show::getPhotoForm()
        ->setFieldValue('show_id', $show->getID())
        ->render();
}
