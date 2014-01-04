<?php

/**
 * Impersonate a user, convincing systems that you *are* them.
 * 
 * @author Lloyd Wallis
 * @data 20140102
 * @package MyRadio_Core
 */

if (isset($_REQUEST['memberid'])) {
    //Impersonate
    $impersonatee = MyRadio_User::getInstance($_REQUEST['memberid']);
    if ((!CoreUtils::hasPermission(AUTH_IMPERSONATE)) ||
            ($impersonatee->hasAuth(AUTH_BLOCKIMPERSONATE) &&
            !CoreUtils::hasPermission(AUTH_IMPERSONATE_BLOCKED_USERS))) {
        require_once 'Controllers/Errors/403.php';
    } else {
        $_SESSION['myradio-impersonating'] = $_SESSION;
        $_SESSION['memberid'] = $impersonatee->getID();
        $ip_auth = Database::getInstance()->fetch_column('SELECT typeid FROM auth_subnet WHERE subnet >>= $1', [$_SERVER['REMOTE_ADDR']]);
        $_SESSION['member_permissions'] = array_merge($ip_auth, $impersonatee->getPermissions());
        $_SESSION['name'] = $impersonatee->getName();
        $_SESSION['email'] = $impersonatee->getEmail();
        $_SESSION['auth_use_locked'] = false;
        $_SESSION['auth_hash'] = sha1(session_id().$_SESSION['name'].$_SESSION['email'].$_SESSION['memberid']);
    }
} else {
    /**
     * For some reason I sometimes have to unimpersonate 3 or more times before
     * the impersonating key actually gets reset...
     */
    while(isset($_SESSION['myradio-impersonating'])) {
        //Unimpersonate
        $impersonate = $_SESSION['myradio-impersonating'];
        session_destroy();
        session_start();
        $_SESSION = $impersonate;
    }
}

if (isset($_REQUEST['next'])) {
    header('Location: '.$_REQUEST['next']);
} else {
    header('Location: '.Config::$base_url);
}