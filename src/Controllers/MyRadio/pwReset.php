<?php

/**
 * @data 20140120
 */
use \MyRadio\Config;
use \MyRadio\MyRadio\MyRadioForm;
use \MyRadio\MyRadio\MyRadioFormField;

$form = (
    new MyRadioForm(
        'myradio_pwReset',
        'MyRadio',
        'pwReset',
        [
            'title' => 'Password Reset',
            'captcha' => true,
        ]
    )
)->addField(
    new MyRadioFormField(
        'user',
        MyRadioFormField::TYPE_TEXT,
        [
            'explanation' => '',
            'label' => 'Username:',
            'options' => ['placeholder' => 'abc123'],
        ]
    )
)->setTemplate('MyRadio/pwReset.twig');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_REQUEST['myradio_pwReset-user'])) {
    //Submitted
    $data = $form->readValues();

    if (!$data) {
        //Invalid captcha
        $form->render(
            ['messages' => ['<div class="alert alert-danger">Please verify the captcha input and try again.</div>']]
        );
    } else {
        foreach (Config::$authenticators as $i) {
            $authenticator = new $i();
            if ($authenticator->resetAccount($data['user'])) {
                break;
            }
        }

        $form->render(
            ['messages' => ['<div class="alert alert-success">Please check your email to finish resetting your password.</div>']]
        );
    }
} else {
    foreach (Config::$authenticators as $authenticator) {
        $auth = new $authenticator();
        $messages[] = $auth->getResetFormMessage();
    }

    //Not Submitted
    $form->render(['messages' => $messages]);
}
