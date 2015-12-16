<?php
/**
 * Assign a Member an Officership
 *
 * @package MyRadio_Profile
 */

use \MyRadio\MyRadioException;
use \MyRadio\MyRadio\URLUtils;
use \MyRadio\ServiceAPI\MyRadio_Officer;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //Submitted
    $data = MyRadio_Officer::getAssignForm()->readValues();

    $officer = MyRadio_Officer::getInstance($data['id']);

    if ($data['member']->isCurrentlyPaid()) {
        $officer->assignOfficer($data['member']->getID());
        URLUtils::backWithMessage('Officership Assigned!');
    } else {
        throw new MyRadioException('Member is not paid!', 400);
    }
} else {
    //Not Submitted

    if (isset($_REQUEST['officerid'])) {
        //assign form
        $officer = MyRadio_Officer::getInstance($_REQUEST['officerid']);

        MyRadio_Officer::getAssignForm()
            ->editMode(
                $officer->getID(),
                []
            )
            ->setTitle('Assign Officer - '. $officer->getName())
            ->render();
    } else {
        // Error
        throw new MyRadioException('Officer ID must be provided.', 400);
    }
}
