<?php

/**
 * Impersonate a user, convincing systems that you *are* them.
 *
 * @data    20140102
 */
use \MyRadio\Database;
use \MyRadio\Config;
use \MyRadio\MyRadio\AuthUtils;
use MyRadio\MyRadio\URLUtils;
use \MyRadio\ServiceAPI\MyRadio_User;
use \MyRadio\ServiceAPI\MyRadio_Timeslot;

if (isset($_REQUEST['memberid'])) {
    //Impersonate
    $impersonatee = MyRadio_User::getInstance($_REQUEST['memberid']);
    if ((!AuthUtils::hasPermission(AUTH_IMPERSONATE))
        || ($impersonatee->hasAuth(AUTH_BLOCKIMPERSONATE)
        && !AuthUtils::hasPermission(AUTH_IMPERSONATE_BLOCKED_USERS))
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

        // Now to reset the timeslot if we should have it once impersonated.
        $timeslot = MyRadio_Timeslot::getUserSelectedTimeslot();
        if ($timeslot) {
            //Can the user access this timeslot?
            if (!($timeslot->isCurrentUserAnOwner() || AuthUtils::hasPermission(AUTH_EDITSHOWS))) {
                MyRadio_Timeslot::setUserSelectedTimeslot(); // Don't have perms, reset it.
            }
        }
    }
} elseif (isset($_SESSION['myradio-impersonating'])) {
    //Unimpersonate
    $impersonate = $_SESSION['myradio-impersonating'];
    // This will jump back the selected timeslot back too.
    $_SESSION = $impersonate;
}

if (isset($_REQUEST['next'])) {
    URLUtils::redirectURI($_REQUEST['next']);
} else {
    URLUtils::redirectURI(Config::$base_url);
}
