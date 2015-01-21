<?php
/**
 * Edit an Officer
 *
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130809
 * @package MyRadio_Profile
 */

use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\ServiceAPI\MyRadio_Officer;




if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //Submitted

    $officer = MyRadio_Officer::getInstance($_REQUEST['assign_officer_permissions-myradiofrmedid']);

    $data = $officer->permissionForm()->readValues();

    $officer->addPermission($data['permission']);

    CoreUtils::backWithMessage('Permission added to Officer');

} else {
    //Not Submitted

    $officer = MyRadio_Officer::getInstance($_REQUEST['officerid']);

    $officer->permissionForm()
        ->setTemplate('Profile/officer.twig')
        ->setTitle($officer->getName())
        ->editMode($officer->getID(), [])
        ->render(
            [
                'officer' => $officer->toDataSource(true)
            ]
        );
}
