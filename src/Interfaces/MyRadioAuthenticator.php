<?php

namespace MyRadio\Iface;

/**
 * An Authenticator processes login requests for a user against a specific
 * user database.
 */
interface MyRadioAuthenticator
{
    /**
     * @param string $user     The username (a full email address, or the prefix if it matches Config::$eduroam_domain).
     *                         if it matches Config::$eduroam_domain).
     * @param string $password The provided password.
     *
     * @return MyRadio_User|false Map the credentials to a MyRadio User on success, or
     *                            return false on failure.
     */
    public function validateCredentials($user, $password);

    /**
     * @param string $user The username (a full email address, or the prefix
     *                     if it matches Config::$eduroam_domain).
     *
     * @return array A list of IDs for the permission flags this user should be
     *               granted.
     */
    public function getPermissions($user);

    /**
     * @param string $user The username (a full email address, or the prefix if it matches Config::$eduroam_domain).
     *                     if it matches Config::$eduroam_domain).
     *
     * @return bool Whether the reset has happened or not. MyRadio will stop
     *              attempting resets once one Authenticator has return true.
     */
    public function resetAccount($user);

    /**
     * A friendly name to explain to users what the login method is.
     */
    public function getFriendlyName();

    /**
     * A friendly description to explain to users what selecting this authenticator does.
     */
    public function getDescription();

    /**
     * A friendly message to display on the "I've forgotten my password" page.
     */
    public function getResetFormMessage();
}
