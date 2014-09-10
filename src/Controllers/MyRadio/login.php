<?php

use \MyRadio\Config;
use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\MyRadio\MyRadioForm, \MyRadio\MyRadio\MyRadioFormField;

/**
 *
 * @author Lloyd Wallis
 * @date 20131230
 * @package MyRadio_Core
 * @todo Throttle quick attempts from one IP with captcha
 * @todo Refactor login code to support Authentication plugins
 */
$form = (
    new MyRadioForm(
        'myradio_login',
        'MyRadio',
        'login',
        [
            'title' => 'Login'
        ]
    )
)->addField(
    new MyRadioFormField(
        'user',
        MyRadioFormField::TYPE_TEXT,
        [
            'explanation' => '',
            'label' => 'Username:',
            'options' => ['placeholder' => 'abc123']
        ]
    )
)->addField(
    new MyRadioFormField(
        'password',
        MyRadioFormField::TYPE_PASSWORD,
        [
            'explanation' => '',
            'label' => 'Password:'
        ]
    )
)->addField(
    new MyRadioFormField(
        'next',
        MyRadioFormField::TYPE_HIDDEN,
        [
            'value' => isset($_REQUEST['next']) ? $_REQUEST['next'] : Config::$base_url
        ]
    )
)->setTemplate('MyRadio/login.twig');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['myradio_login-user'])) {
    //Submitted
    $status = null;
    $data = $form->readValues();

    $raw_uname = str_replace('@' . Config::$eduroam_domain, '', $data['user']);

    $authenticators = [];
    foreach (Config::$authenticators as $i) {
        $authenticator = new $i();
        $user = $authenticator->validateCredentials($raw_uname, $data['password']);

        if ($user) {
            if ($user->getAccountLocked()) {
                //The user's account is disabled
                $status = 'locked';
                break;
            } elseif (Config::$single_authenticator &&
                    $user->getAuthProvider() != null && $user->getAuthProvider() != $i) {
                //They can only authenticate with the right provider once they've set one
                //(if they haven't yet, we'll ask them to choose one)
                $status = 'wrongAuthProvider';
            } else {
                $_SESSION['memberid'] = (int) $user->getID();
                /**
                 * Add in permissions granted by the remote IP
                 * Contains or equals: >>=
                 */
                $ip_auth = Database::getInstance()->fetchColumn(
                    'SELECT typeid FROM auth_subnet WHERE subnet >>= $1',
                    [$_SERVER['REMOTE_ADDR']]
                );
                $_SESSION['member_permissions'] = array_map(
                    function ($x) {
                        return (int) $x;
                    },
                    array_merge($ip_auth, $user->getPermissions(), $authenticator->getPermissions($raw_uname))
                );
                $_SESSION['name'] = $user->getName();
                $_SESSION['email'] = $user->getEmail();
                /*
                 * If anything other than false, the user will be kicked out if
                 * they try to access anything other than pages with AUTH_NOACCESS
                 */
                $_SESSION['auth_use_locked'] = false;
                if(!$user->isActiveMemberForYear()) {
                    $user->activateMemberThisYear();
                }
                $user->updateLastLogin();
                $status = 'success';
                $authenticators[$i] = true;
                if ($user->getRequirePasswordChange()) {
                    //The user needs to change their password
                    $_SESSION['auth_use_locked'] = 'changePassword';
                    $status = 'change';
                }

                //If the user needs to specify an auth provider, go through all login mechanisms
                if (Config::$single_authenticator && !$user->getAuthProvider()) {
                    $_SESSION['auth_use_locked'] = 'chooseAuth';
                    $status = 'choose';
                } else {
                    break;
                }
            }
        } else {
            $authenticators[$i] = false;
        }
    }

    if ($status === 'choose') {
        //The user needs to set a login provider
        $twig = CoreUtils::getTemplateObject()->setTemplate('MyRadio/chooseAuth.twig')
            ->addVariable('title', 'Choose Login Method');
        $options = [];
        $chosen_default = false;
        foreach ($authenticators as $authenticator => $success) {
            $a = new $authenticator();
            $option = [
                'value' => $authenticator,
                'name' => $a->getFriendlyName(),
                'description' => $a->getDescription(),
                'different' => !$success,
                'default' => false
            ];
            if ($success && !$chosen_default) {
                $option['default'] = true;
                $chosen_default = true;
            }
            $options[] = $option;
        }
        $twig->addVariable('methods', $options)
            ->addVariable('next', isset($data['next']) ? $data['next'] : CoreUtils::makeURL(Config::$default_module))
            ->render();
    } elseif ($status === 'change') {
        CoreUtils::redirect('MyRadio', 'pwChange');
    } elseif ($status !== 'success') {
        $form->render(['error' => true]);
    } else {
        if (isset($data['next'])) {
            header('Location: ' . $data['next']);
        } else {
            CoreUtils::redirect(Config::$default_module);
        }
    }
} else {
    //Not Submitted
    $form->render(['logout' => isset($_REQUEST['logout'])]);
}
