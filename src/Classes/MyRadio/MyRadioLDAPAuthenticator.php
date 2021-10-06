<?php

namespace MyRadio\MyRadio;

use MyRadio\Config;
use MyRadio\ServiceAPI\MyRadio_User;

/**
 * An Authenticator processes login requests for a user against a specific
 * user database.
 */
class MyRadioLDAPAuthenticator implements \MyRadio\Iface\MyRadioAuthenticator
{
    private $ldap_handle;

    /**
     * Sets up the LDAP connection.
     */
    public function __construct()
    {
        $this->ldap_handle = ldap_connect(Config::$auth_ldap_server);
    }
    /**
     * Tears down the LDAP connection.
     */
    public function __destruct()
    {
        ldap_close($this->ldap_handle);
    }
    /**
     * @param string $user     The username (a full email address, or the prefix if it matches Config::$eduroam_domain).
     *                         if it matches Config::$eduroam_domain).
     * @param string $password The provided password.
     *
     * @return MyRadio_User|false Map the credentials to a MyRadio User on success, or
     *                            return false on failure.
     */
    public function validateCredentials($user, $password)
    {
        # Check that it looks like a legit user first.
        if (!empty(Config::$auth_ldap_regex)) {
            if (!preg_match($auth_ldap_regex, $user)) {
                sleep(0.01); # Emulate the speed of LDAP to help with timing attacks.
                return false;
            }
        }
        if (@ldap_bind($this->ldap_handle, 'uid='.$user.','.Config::$auth_ldap_root, $password)) {
            return MyRadio_User::findByEmail($user);
        } else {
            return false;
        }
    }
    /**
     * This authenticator does not add any extra permissions to the ones already
     * defined internally.
     *
     * @param string $user The username (a full email address, or the prefix
     *                     if it matches Config::$eduroam_domain).
     *
     * @return array A list of IDs for the permission flags this user should be
     *               granted. These are in addition to the ones computed by MyRadio
     *               internally.
     */
    public function getPermissions($user)
    {
        return [];
    }
    /**
     * This authenticator can not process password resets.
     *
     * @param string $user The username (a full email address, or the prefix if it matches Config::$eduroam_domain).
     *                     if it matches Config::$eduroam_domain).
     *
     * @return bool Whether the reset has happened or not. MyRadio will stop
     *              attempting resets once one Authenticator has return true.
     */
    public function resetAccount($user)
    {
        return false;
    }

    public function getFriendlyName()
    {
        return Config::$auth_ldap_friendly_name;
    }

    public function getDescription()
    {
        return 'By choosing this option, we will always use your '
                .$this->getFriendlyName().' username and password to log you in.'
                .' Whenever you change your '.$this->getFriendlyName()
                .' password, your '.Config::$short_name
                .' password will also change.';
    }

    public function getResetFormMessage()
    {
        return '<div class="alert alert-info">Have you tried using your '
               .$this->getFriendlyName()
               .' username and password? <a href="'
               .Config::$auth_ldap_reset_url
               .'">If you can\'t remember your '
               .$this->getFriendlyName()
               .' login, please click here.</a></div>';
    }
}

