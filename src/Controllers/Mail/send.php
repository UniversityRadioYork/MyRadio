<?php
/**
 * Send an email to a mailing list.
 */
use \MyRadio\Config;
use \MyRadio\MyRadioException;
use \MyRadio\MyRadioEmail;
use \MyRadio\MyRadio\AuthUtils;
use \MyRadio\MyRadio\URLUtils;
use \MyRadio\MyRadio\MyRadioForm;
use \MyRadio\MyRadio\MyRadioFormField;
use \MyRadio\ServiceAPI\MyRadio_List;
use \MyRadio\ServiceAPI\MyRadio_User;

$form = (
    new MyRadioForm(
        'mail_send',
        $module,
        $action,
        [
            'debug' => true,
            'title' => 'Send Email',
        ]
    )
)->addField(
    new MyRadioFormField(
        'subject',
        MyRadioFormField::TYPE_TEXT,
        [
            'placeholder' => 'Subject (['.Config::$short_name.'] is prefixed automatically)',
        ]
    )
)->addField(
    new MyRadioFormField(
        'body',
        MyRadioFormField::TYPE_BLOCKTEXT,
        []
    )
)->addField(
    new MyRadioFormField(
        'list',
        MyRadioFormField::TYPE_HIDDEN,
        []
    )
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //Submitted
    $data = $form->readValues();

    MyRadioEmail::sendEmailToList(
        MyRadio_List::getInstance($data['list']),
        $data['subject'],
        $data['body'],
        MyRadio_User::getInstance()
    );

    URLUtils::backWithMessage('Message sent!');
} else {
    //Not Submitted
    if (!isset($_REQUEST['list'])) {
        throw new MyRadioException('List ID was not provided!', 400);
    }
    if (!MyRadio_List::getInstance($_REQUEST['list'])->isPublic()) {
        AuthUtils::requirePermission(AUTH_MAILALLMEMBERS);
    }

    $form->setFieldValue(
        'list',
        $_REQUEST['list']
    )->setTemplate(
        'Mail/send.twig'
    )->render(
        ['rcpt_str' => MyRadio_List::getInstance($_REQUEST['list'])->getName()]
    );
}
