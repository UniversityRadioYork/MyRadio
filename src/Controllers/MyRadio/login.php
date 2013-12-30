<?php

/**
 *
 * @author Lloyd Wallis
 * @data 20131230
 * @package MyRadio_Core
 * @todo Throttle quick attempts from one IP with captcha
 * @todo Refactor login code to support Authentication plugins
 */
$form = (new MyRadioForm('myradio_login', 'MyRadio', 'login', array(
    'title' => 'Login'
        )
        ))->addField(
                new MyRadioFormField('user', MyRadioFormField::TYPE_TEXT, array(
            'explanation' => '',
            'label' => 'Username:',
            'options' => ['placeholder' => 'abc123']
                ))
        )->addField(
                new MyRadioFormField('password', MyRadioFormField::TYPE_PASSWORD, array(
            'explanation' => '',
            'label' => 'Password:'
                ))
        )->setTemplate('MyRadio/login.twig');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //Submitted
    $status = null;
    $data = $form->readValues();

    $raw_uname = str_replace('@' . Config::$eduroam_domain, '', $data['user']);

    foreach (Config::$authenticators as $i) {
        $authenticator = new $i();
        $user = $authenticator->validateCredentials($raw_uname, $data['password']);

        if ($user) {
            $_SESSION['memberid'] = $user->getID();
            $_SESSION['member_permissions'] = array_merge($user->getPermissions(),
                    $authenticator->getPermissions($raw_uname));
            $_SESSION['name'] = $user->getName();
            $_SESSION['email'] = $user->getEmail();
            $user->updateLastLogin();
            $status = 'success';
            break;
        }
    }

    if ($status !== 'success') {
        $form->render(['error' => true]);
    } else {
        if ($data['next']) {
            header('Location: ' . $data['next']);
        } else {
            header('Location: ' . CoreUtils::makeURL(Config::$default_module));
        }
    }
} else {
    //Not Submitted
    $form->render(['logout' => isset($_REQUEST['logout'])]);
}