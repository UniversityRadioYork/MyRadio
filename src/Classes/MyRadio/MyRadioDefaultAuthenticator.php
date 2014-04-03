<?php

/**
 * An Authenticator processes login requests for a user against a specific
 * user database.
 *
 * @author Lloyd Wallis <lpw@ury.org.uk>
 */
class MyRadioDefaultAuthenticator extends Database implements MyRadioAuthenticator
{
    /**
     * Sets up the DB connection
     */
    public function __construct()
    {
        $this->db = pg_connect(
            'host=' . Config::$db_hostname . ' port=5432 dbname=' . Config::$db_name
            . ' user=' . Config::$auth_db_user . ' password=' . Config::$auth_db_pass
        );
        if (!$this->db) {
            //Database isn't working. Throw an EVERYTHING IS BROKEN Exception
            throw new MyRadioException('Database Connection Failed!', MyRadioException::FATAL);
        }
    }

    /**
     * Tears down the DB connection
     */
    public function __destruct()
    {
        pg_close($this->db);
    }

    /**
     * @param  String             $user     The username (a full email address, or the prefix
     *                                      if it matches Config::$eduroam_domain).
     * @param  String             $password The provided password.
     * @return MyRadio_User|false Map the credentials to a MyRadio User on success, or
     *                                     return false on failure.
     * @todo Require change password
     * @todo Account lock
     * @todo Make timing consistent
     */
    public function validateCredentials($user, $password)
    {
        //If local passwords are disabled, don't even try.
        if (!Config::$enable_local_passwords) {
            return false;
        }
        //Find the member in our DB
        $user = MyRadio_User::findByEmail($user);
        if (!$user) {
            return false;
        } else {
            $r = $this->fetchColumn(
                'SELECT password FROM '
                . 'public.member_pass WHERE memberid=$1',
                [$user->getID()]
            );
            if (empty($r)) {
                return false;
            } else {
                //Validate the password
                if (crypt($password, $r[0]) === $r[0]) {
                    //Check if the password is legacy MD5
                    if (substr($r[0], 0, 3) === '$1$') {
                        //Upgrade password
                        $new_password = $this->encrypt($password);
                        $this->query(
                            'UPDATE member_pass SET password=$1 WHERE memberid=$2',
                            [$new_password, $user->getID()]
                        );
                    }
                    unset($password, $new_password, $r); //Just to be safe.

                    return $user;
                } else {
                    return false;
                }
            }
        }
    }

    /**
     * This authenticator does not add any extra permissions to the ones already
     * defined internally.
     *
     * @param  String $user The username (a full email address, or the prefix
     *                      if it matches Config::$eduroam_domain).
     * @return Array  A list of IDs for the permission flags this user should be
     *                     granted. These are in addition to the ones computed by MyRadio
     *                     internally.
     */
    public function getPermissions($user)
    {
        return [];
    }

    /**
     * This authenticator will reset the password in the MyRadio database.
     *
     * @param  String  $user The username (a full email address, or the prefix
     *                       if it matches Config::$eduroam_domain).
     * @return boolean Whether the reset has happened or not. MyRadio will stop
     *                      attempting resets once one Authenticator has return true.
     * @todo implement password resets
     */
    public function resetAccount($user)
    {
        $result = MyRadio_User::findByEmail($user);
        if (!$result) {
            return false;
        }

        $db = Database::getInstance();

        //Create a reset token
        do {
            $token = CoreUtils::randomString(64);
        } while ($db->numRows(
            $db->query('SELECT * FROM myury.password_reset_token WHERE token=$1', [$token])) > 0
            );

        //Add the reset token to the database (expires in 48h)
        $expires = CoreUtils::getTimestamp(time() + 86400 * 2);
        $db->query(
            'INSERT INTO myury.password_reset_token (token, memberid, expires) VALUES ($1, $2, $3)',
            [$token, $result->getID(), $expires]
        );

        //Email the user
        MyRadioEmail::sendEmailToUser(
            $result,
            'Password reset',
            'Hello,'
            . '<p>A password reset has been requested for the ' . Config::$short_name
            . ' account associated with this email address. If you did not request'
            . ' this email, please ignore it.</p>'
            . '<p><a href="' . CoreUtils::makeURL('MyRadio', 'pwChange', ['token' => $token])
            . '">Click here to finish resetting your password.</a></p>'
        );

        return true;
    }

    /**
     * Encrypts a password using MyRadio's prefered technique.
     *
     * @param  String $string The string to be encrypted
     * @return String The encrypted string
     */
    private function encrypt($string)
    {
        return crypt($string, '$6$rounds=4567$' . $this->randomString());
    }

    /**
     * Generates a cryptographically secure pseudorandom string, for Salt purposes.
     * @param  int    $pwdLen The length of the string to generate
     * @return String a random string of length $pwdLen
     */
    private function randomString($pwdLen = 32)
    {
        return base64_encode(openssl_random_pseudo_bytes($pwdLen));
    }

    public function getFriendlyName()
    {
        return Config::$short_name . '-only';
    }

    public function getDescription()
    {
        return 'By choosing this option, we will always use your unique '
        . $this->getFriendlyName()
        . ' username and password to log you in. '
        . 'This password is completely seperate to any other details '
        . 'you may have.';
    }

    public function removePassword($memberid)
    {
        $this->query('UPDATE member_pass SET password=NULL WHERE memberid=$1', [$memberid]);
    }

    /**
     * Sets a User's password.
     *
     * @param User $user
     * @param String $password
     */
    public function setPassword(MyRadio_User $user, $password)
    {
        $password = $this->encrypt($password);
        //Insert or Update
        $result = $this->query(
            'UPDATE member_pass SET password=$1 WHERE memberid=$2',
            [$password, $user->getID()]
        );

        //Set require_password_change to false
        $user->setRequirePasswordChange(false);

        if (pg_affected_rows($result) === 0) {
            $this->query(
                'INSERT INTO member_pass (memberid, password) VALUES ($1, $2)',
                [$user->getID(), $password]
            );
        }
    }

    public function getResetFormMessage()
    {
        //If this is not the only authenticator, mention this will create a
        //MyRadio specific login.
        if (sizeof(Config::$authenticators) > 1) {
            $others = '';
            foreach (Config::$authenticators as $auth) {
                if ($auth !== __CLASS__) {
                    $a = new $auth;
                    $others .= (empty($others) ? '' : ', ') . $a->getFriendlyName();
                }
            }
            return 'If you do not currently have a ' . Config::$short_name .
                    ' password, this will enable you to set one up which is'
                    . ' seperate to your ' . $others . ' password.';
        } else {
            return 'If you\'ve forgotten your ' . Config::$short_name . ' password, you'
                    . ' can fill in this form to have a reset email sent to you.';
        }
    }

}
