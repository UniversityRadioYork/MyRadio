<?php
/**
 * Forces users to accept privacy policy on first login
 */
use \MyRadio\Config;
use \MyRadio\Database;
use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\MyRadio\URLUtils;
use \MyRadio\MyRadio\MyRadioForm;
use \MyRadio\MyRadio\MyRadioFormField;
use MyRadio\ServiceAPI\MyRadio_User;

$form = (
    new MyRadioForm(
        'myradio_login',
        'MyRadio',
        'privacystatement',
        [
            'title' => 'Privacy Statement'
        ]
    )
    )->setTemplate('MyRadio/privacy.twig');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $user = MyRadio_User::getInstance($_SESSION['memberid']);
    $user->setSignedGDPR(true);
    URLUtils::redirect(Config::$default_module);

} else {
    //Not Submitted
    $form->render(['logout' => isset($_REQUEST['logout'])]);
}