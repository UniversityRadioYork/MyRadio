<?php
/**
 * Assign a Member an Officership
 *
 * @package MyRadio_Profile
 */

use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\ServiceAPI\MyRadio_Officer;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //Submitted
    $data = MyRadio_Officer::getAssignForm()->readValues();

    $officer = MyRadio_Officer::getInstance($data['id']);

    $officer->assignOfficer($data['member']->getID());

    CoreUtils::backWithMessage('Officership Assigned!');

} else {
    //Not Submitted

    if (isset($_REQUEST['officerid'])) {
        //assign form
        $officer = MyRadio_Officer::getInstance($_REQUEST['officerid']);

        MyRadio_Officer::getAssignForm()
            ->editMode(
                $officer->getID(),
                []
            )->render();

    } else {
        // Error
        throw new MyRadioException('Officer ID must be provided.', 400);
    }
}
