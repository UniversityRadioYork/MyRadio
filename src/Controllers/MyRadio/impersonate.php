<?php

/**
 * Impersonate a user, convincing systems that you *are* them.
 *
 * @author  Lloyd Wallis
 * @data    20140102
 * @package MyRadio_Core
 */

use \MyRadio\Database;
use \MyRadio\Config;
use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\ServiceAPI\MyRadio_User;

if (isset($_REQUEST['memberid'])) {
    //Impersonate
    $impersonatee = MyRadio_User::getInstance($_REQUEST['memberid']);
    if ((!CoreUtils::hasPermission(AUTH_IMPERSONATE))
        || (        $impersonatee->hasAuth(AUTH_BLOCKIMPERSONATE)
        && !CoreUtils::hasPermission(AUTH_IMPERSONATE_BLOCKED_USERS))
    ) {
        require_once 'Controllers/Errors/403.php';
    } else {
        // Yes, this temporary variable is necessary, otherwise recursion happens.
        // I don't even.
        $old_sess = $_SESSION;
        $_SESSION['myradio-impersonating'] = $old_sess;
        $_SESSION['memberid'] = $impersonatee->getID();
        $ip_auth = Database::getInstance()->fetchColumn(
            'SELECT typeid FROM auth_subnet WHERE subnet >>= $1',
            [$_SERVER['REMOTE_ADDR']]
        );
        $_SESSION['member_permissions'] = array_merge($ip_auth, $impersonatee->getPermissions());
        $_SESSION['name'] = $impersonatee->getName();
        $_SESSION['email'] = $impersonatee->getEmail();
        $_SESSION['auth_use_locked'] = false;
    }
} elseif (isset($_SESSION['myradio-impersonating'])) {
    //Unimpersonate
    $impersonate = $_SESSION['myradio-impersonating'];
    $_SESSION = $impersonate;
}

if (isset($_REQUEST['next'])) {
    header('Location: ' . $_REQUEST['next']);
} else {
    header('Location: ' . Config::$base_url);
}
