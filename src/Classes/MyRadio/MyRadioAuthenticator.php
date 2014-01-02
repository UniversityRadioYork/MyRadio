<?php

/**
 * An Authenticator processes login requests for a user against a specific
 * user database.
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 */
interface MyRadioAuthenticator {
    /**
     * @param String $user The username (a full email address, or the prefix
     * if it matches Config::$eduroam_domain).
     * @param String $password The provided password.
     * @return User|false Map the credentials to a MyRadio User on success, or
     * return false on failure.
     */
    public function validateCredentials($user, $password);
    /**
     * @param String $user The username (a full email address, or the prefix
     * if it matches Config::$eduroam_domain).
     * @return Array A list of IDs for the permission flags this user should be
     * granted.
     */
    public function getPermissions($user);
    /**
     * @param String $user The username (a full email address, or the prefix
     * if it matches Config::$eduroam_domain).
     * @return boolean Whether the reset has happened or not. MyRadio will stop
     * attempting resets once one Authenticator has return true.
     */
    public function resetAccount($user);
    
    /**
     * A friendly name to explain to users what the login method is
     */
    public function getFriendlyName();
    
    /**
     * A friendly description to explain to users what selecting this authenticator does
     */
    public function getDescription();
}
