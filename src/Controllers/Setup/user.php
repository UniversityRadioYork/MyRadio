<?php
/**
 * Sets up the initial admin user for MYRadio.
 */
use \MyRadio\Config;
use \MyRadio\MyRadio\AuthUtils;
use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\ServiceAPI\MyRadio_User;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_REQUEST['password'] === $_REQUEST['passwordchk']) {
    $params = [
        'fname' => $_REQUEST['first-name'],
        'sname' => $_REQUEST['last-name'],
        'email' => $_REQUEST['email'],
        'phone' => $_REQUEST['phone'],
        'paid' => Config::$membership_fee,
        'provided_password' => $_REQUEST['password']
    ];
    $user = MyRadio_User::create($params);

    // Give this user most possible permissions
    AuthUtils::setUpAuth();
    foreach (json_decode(file_get_contents(SCHEMA_DIR.'data-auth.json')) as $auth) {
        if (!$auth[2] or !defined($auth[1])) {
            continue;
        }
        $user->grantPermission(constant($auth[1]));
    }

    header('Location: ?c=save');
} else {
    CoreUtils::getTemplateObject()
        ->setTemplate('Setup/user.twig')
        ->addVariable('title', 'User')
        ->addVariable('db_error', isset($_GET['err']))
        ->render();
}
