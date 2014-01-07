<?php

/**
 * Sets the user's prefered Authenticator
 * @author Lloyd Wallis
 * @data 20140102
 * @package MyRadio_Core
 */

if (!isset($_REQUEST['authenticator'])) {
    header('Location: '.CoreUtils::makeURL('MyRadio','login'));
    exit;
}

if (!in_array($_REQUEST['authenticator'], Config::$authenticators)) {
    throw new MyRadioException($_REQUEST['authenticator'].' is not a valid Authenticator', 400);
}

//Set the authenticator
MyRadio_User::getInstance()->setAuthProvider($_REQUEST['authenticator']);

//If it's not the Default authenticator, delete the password
if ($_REQUEST['authenticator'] !== 'MyRadioDefaultAuthenticator') {
    (new MyRadioDefaultAuthenticator())->removePassword($_SESSION['memberid']);
}

//Remove the lock on Session access
$_SESSION['auth_use_locked'] = false;

header('Location: '.$_REQUEST['next']);