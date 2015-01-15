<?php

/**
 * Enables a user to change their password, either whilst logged in or by
 * using a password reset token that has been emailed to them.
 *
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @data 20140121
 * @package MyRadio_Core
 */

use \MyRadio\Config;
use \MyRadio\Database;
use \MyRadio\MyRadioException;
use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\MyRadio\URLUtils;
use \MyRadio\MyRadio\MyRadioForm;
use \MyRadio\MyRadio\MyRadioFormField;
use \MyRadio\MyRadio\MyRadioDefaultAuthenticator;
use \MyRadio\ServiceAPI\MyRadio_User;

$form = (
    new MyRadioForm(
        'myradio_pwChange',
        'MyRadio',
        'pwChange',
        [
            'title' => 'Password Change'
        ]
    )
)->addField(
    new MyRadioFormField(
        'pw1',
        MyRadioFormField::TYPE_PASSWORD,
        [
            'explanation' => '',
            'label' => 'New Password:'
        ]
    )
)->addField(
    new MyRadioFormField(
        'pw2',
        MyRadioFormField::TYPE_PASSWORD,
        [
            'explanation' => '',
            'label' => 'Confirm New Password:'
        ]
    )
)->setTemplate('MyRadio/pwReset.twig');

/**
 * If the user is logged in, we're changing their password. Ask them to verify
 * their existing one. If they aren't logged in, then they should be following
 * a password reset link, in which case we verify the reset token.
 */
if (isset($_SESSION['memberid'])) {
    $form->addField(
        new MyRadioFormField(
            'pwold',
            MyRadioFormField::TYPE_PASSWORD,
            [
                'explanation' => '',
                'label' => 'Current Password:'
            ]
        )
    );
} else {
    $var = $_SERVER['REQUEST_METHOD'] === 'POST' ? 'myradio_pwChange-token' : 'token';

    if (!isset($_REQUEST[$var])) {
        throw new MyRadioException('Password reset token required.', 400);
    } else {
        $db = Database::getInstance();
        $token = $db->fetchOne(
            'SELECT * FROM myury.password_reset_token
            WHERE token=$1 AND expires > NOW() AND used IS NULL',
            [$_REQUEST[$var]]
        );

        if (empty($token)) {
            throw new MyRadioException('Password reset token invalid. It may have expired or already been used.', 400);
        } else {
            $form->addField(
                new MyRadioFormField(
                    'token',
                    MyRadioFormField::TYPE_HIDDEN,
                    [
                        'value' => $token['token']
                    ]
                )
            );
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_REQUEST['myradio_pwChange-pw1'])) {
    //Submitted
    $data = $form->readValues();

    if ($data['pw1'] !== $data['pw2']) {
        //Passwords do not match
        $form->render(['error' => 'Your new passwords did not match.']);
        exit;
    }

    //Logged in user change?
    if (isset($data['pwold'])) {
        //Is the old password correct?
        if (CoreUtils::testCredentials(MyRadio_User::getInstance()->getEmail(), $data['pwold']) === false) {
            $form->render(['error' => 'Your old password was invalid.']);
            exit;
        }
        $user = MyRadio_User::getInstance();
    } else { //Reset token change
        //Token initialised in form definition above.
        $user = MyRadio_User::getInstance($token['memberid']);
    }

    //Right, let's update the password
    /**
     * Only works with MyRadioDefaultAuthenticator. Should it allow
     * others to plug in? I think not.
     */
    $authenticator = new MyRadioDefaultAuthenticator();
    $authenticator->setPassword($user, $data['pw1']);
    unset($data);

    //Reset the User's authenticator preferences - they may be locking them out
    $user->setAuthProvider(null);

    //Set the token as used
    if (isset($token)) {
        $db->query(
            'UPDATE myury.password_reset_token SET used=NOW()
            WHERE token=$1',
            [$token['token']]
        );
    }

    //If the user was locked out for a password change, unlock them
    if (isset($_SESSION['auth_use_locked'])
        && $_SESSION['auth_use_locked'] === 'chooseAuth') {
        unset($_SESSION['auth_use_locked']);
    }

    URLUtils::redirect('MyRadio', 'login');
} else {
    foreach (Config::$authenticators as $authenticator) {
        $auth = new $authenticator;
        $messages[] = $auth->getResetFormMessage();
    }

    //Not Submitted
    $form->render(['messages' => $messages]);
}
