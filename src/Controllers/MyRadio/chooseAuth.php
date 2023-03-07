<?php

/**
 * Sets the user's prefered Authenticator.
 *
 * @data 20140102
 */
use \MyRadio\Config;
use \MyRadio\MyRadioException;
use \MyRadio\MyRadio\URLUtils;
use \MyRadio\MyRadio\MyRadioDefaultAuthenticator;
use \MyRadio\ServiceAPI\MyRadio_User;

if (!isset($_REQUEST['authenticator'])) {
    URLUtils::redirect('MyRadio', 'login');
    exit;
}

if (!in_array($_REQUEST['authenticator'], Config::$authenticators)) {
    throw new MyRadioException($_REQUEST['authenticator'].' is not a valid Authenticator', 400);
}

//Set the authenticator
MyRadio_User::getInstance()->setAuthProvider($_REQUEST['authenticator']);

//If it's not the Default authenticator, delete the password and make require password change false
if ($_REQUEST['authenticator'] !== '\MyRadio\MyRadio\MyRadioDefaultAuthenticator') {
    (new MyRadioDefaultAuthenticator())->removePassword($_SESSION['memberid']);
    MyRadio_User::getInstance()->setRequirePasswordChange(false);
}

//Remove the lock on Session access
$_SESSION['auth_use_locked'] = false;

URLUtils::redirectURI($_REQUEST['next']);
