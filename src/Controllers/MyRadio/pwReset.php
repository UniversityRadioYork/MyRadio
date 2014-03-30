<?php

/**
 *
 * @author Lloyd Wallis
 * @data 20140120
 * @package MyRadio_Core
 */
$form = (new MyRadioForm('myradio_pwReset', 'MyRadio', 'pwReset', array(
    'title' => 'Password Reset',
    'captcha' => true
        )
        ))->addField(
                new MyRadioFormField('user', MyRadioFormField::TYPE_TEXT, array(
            'explanation' => '',
            'label' => 'Username:',
            'options' => ['placeholder' => 'abc123']
                ))
        )->setTemplate('MyRadio/pwReset.twig');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_REQUEST['myradio_pwReset-user'])) {
    //Submitted
    $data = $form->readValues();

    if (!$data) {
        //Invalid captcha
        $form->render(['messages' => ['<div class="ui-state-error">Please verify the captcha input and try again.</div>']]);
    } else {
        foreach (Config::$authenticators as $i) {
            $authenticator = new $i;
            if ($authenticator->resetAccount($data['user'])) {
                break;
            }
        }

        $form->render(['messages' => ['<div class="ui-state-highlight">Please check your email to finish resetting your password.</div>']]);
    }
} else {
    foreach (Config::$authenticators as $authenticator) {
        $auth = new $authenticator;
        $messages[] = $auth->getResetFormMessage();
    }

    //Not Submitted
    $form->render(['messages' => $messages]);
}
