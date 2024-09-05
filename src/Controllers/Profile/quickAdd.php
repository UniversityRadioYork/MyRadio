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
        $params['nname'],
        $params['sname'],
        $params['eduroam'],
        $params['collegeid'],
        null,
        $params['phone']
    );

    if ($user === null) {
        $msg = 'This member already has an account!';
    } else {
        $msg = 'New Member has been created with ID '.$user->getID();
    }
    URLUtils::backWithMessage($msg);
} else {
    //Not Submitted
    MyRadio_User::getQuickAddForm()->render();
}
