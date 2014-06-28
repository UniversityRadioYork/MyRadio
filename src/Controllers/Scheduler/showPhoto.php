<?php
/**
 * Set the show photo
 *
 * @author Andy Durant <aj@ury.org.uk>
 * @version 20140624
 * @package MyRadio_Scheduler
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //Submitted
    $data = MyRadio_Show::getPhotoForm()->readValues();

    $show = MyRadio_Show::getInstance($data['show_id']);
    //Require this is the user's show or the user can edit any show
    if (!$show->isCurrentUserAnOwner()) {
        CoreUtils::requirePermission(AUTH_EDITSHOWS);
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
        CoreUtils::requirePermission(AUTH_EDITSHOWS);
    }

    MyRadio_Show::getPhotoForm()
        ->setFieldValue('show_id', $show->getID())
        ->render();
}
