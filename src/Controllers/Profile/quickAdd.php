<?php
/**
 * Allows creation of new URY members!
 */
use \MyRadio\MyRadio\URLUtils;
use \MyRadio\ServiceAPI\MyRadio_User;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //Submitted
    $params = MyRadio_User::getQuickAddForm()->readValues();
    $user = MyRadio_User::createOrActivate(
        $params['fname'],
        $params['sname'],
        $params['eduroam'],
        $params['collegeid'],
        null,
        $params['phone']
    );

    URLUtils::backWithMessage('New Member has been created with ID '.$user->getID());
} else {
    //Not Submitted
    MyRadio_User::getQuickAddForm()->render();
}
