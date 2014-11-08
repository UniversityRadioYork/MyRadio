<?php
/**
 * Sets up the initial admin user for MYRadio
 *
 * @version 20140515
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @package MyRadio_Core
 */
use \MyRadio\APCProvider;
use \MyRadio\Config;
use \MyRadio\Database;
use \MyRadio\IFace\CacheProvider;
use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\MyRadioEmail;
use \MyRadio\ServiceAPI\MyRadio_User;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = MyRadio_User::create(
        $_REQUEST['first-name'],
        $_REQUEST['last-name'],
        null,
        $_REQUEST['gender'],
        null,
        $_REQUEST['email'],
        empty($_REQUEST['phone']) ? null : $_REQUEST['phone'],
        true,
        Config::$membership_fee,
        $_REQUEST['password']
    );

    // Give this user most possible permissions
    CoreUtils::setUpAuth();
    foreach (json_decode(file_get_contents(SCHEMA_DIR . 'data-auth.json')) as $auth) {
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
