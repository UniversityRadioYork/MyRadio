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
    $data = MyRadio_Officer::getForm()->readValues();

    if (empty($data['id'])) {
        //create new
        $officer = MyRadio_Officer::createOfficer(
            $data['name'],
            $data['description'],
            $data['alias'],
            $data['ordering'],
            $data['team'],
            $data['type']
        );

    } else {
        //submit edit
        $officer = MyRadio_Officer::getInstance($data['id']);

    }

    CoreUtils::backWithMessage('Officer Updated!');

} else {
    //Not Submitted

    if (isset($_REQUEST['officerid'])) {
        //edit form
        $officer = MyRadio_Officer::getInstance($_REQUEST['officerid']);

        $officer
            ->getEditForm()
            ->render();

    } else {
        //create form
        MyRadio_Officer::getForm()->render();
    }

}
