<?php

/**
 * Sets the user's prefered Authenticator
 * @author Lloyd Wallis
 * @data 20140102
 * @package MyRadio_Core
 */

use \MyRadio\Config;
use \MyRadio\MyRadioException;
use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\MyRadio\MyRadioDefaultAuthenticator;
use \MyRadio\ServiceAPI\MyRadio_User;

if (!isset($_REQUEST['authenticator'])) {
    CoreUtils::redirect('MyRadio', 'login');
    exit;
}

if (!in_array($_REQUEST['authenticator'], Config::$authenticators)) {
    throw new MyRadioException($_REQUEST['authenticator'].' is not a valid Authenticator', 400);
}

//Set the authenticator
MyRadio_User::getInstance()->setAuthProvider($_REQUEST['authenticator']);

//If it's not the Default authenticator, delete the password and make require password change false
if ($_REQUEST['authenticator'] !== 'MyRadioDefaultAuthenticator') {
    (new MyRadioDefaultAuthenticator())->removePassword($_SESSION['memberid']);
    MyRadio_User::getInstance()->setRequirePasswordChange(false);
}

//Remove the lock on Session access
$_SESSION['auth_use_locked'] = false;

header('Location: '.$_REQUEST['next']);
