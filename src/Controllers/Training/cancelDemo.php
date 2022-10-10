<?php

use MyRadio\MyRadio\AuthUtils;
use MyRadio\MyRadio\MyRadioForm;
use MyRadio\MyRadio\MyRadioFormField;
use MyRadio\MyRadio\URLUtils;
use MyRadio\MyRadioException;
use MyRadio\ServiceAPI\MyRadio_Demo;
use MyRadio\ServiceAPI\MyRadio_User;

if (!isset($_REQUEST['demo_id']) && !isset($_REQUEST['cancelDemo-demo_id'])) {
    URLUtils::backWithMessage('No Demo selected.');
    exit;
}
$demo = MyRadio_Demo::getInstance($_REQUEST['demo_id'] ?? $_REQUEST['cancelDemo-demo_id']);

if ($demo->getDemoer()->getID() !== MyRadio_User::getCurrentUser()->getID()) {
    AuthUtils::requirePermission(AUTH_CANCELANYDEMO);
}

$form = (new MyRadioForm('cancelDemo', 'Training', 'cancelDemo'))
    ->addField(new MyRadioFormField('demo_id', MyRadioFormField::TYPE_HIDDEN))
    ->setFieldValue('demo_id', $_REQUEST['demo_id']);

$attendees = $demo->attendingDemoCount();
if ($attendees > 0) {
    $form = $form->addField(new MyRadioFormField('cancel_attendees', MyRadioFormField::TYPE_CHECK, [
        'label' => 'Confirm cancellation of training with attendees',
        'explanation' => "This training session has $attendees attendees. If you cancel, they will receive an email notifying them."
    ]));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = $form->readValues();
    try {
        $demo->delete($data['cancel_attendees']);
    } catch (MyRadioException $e) {
        if ($e->getCode() === 409) {
            URLUtils::backWithMessage('This demo has attendees, please confirm you wish to cancel it.');
        } else {
            throw $e;
        }
    }
    URLUtils::redirectWithMessage('Training', 'listDemos', 'Demo cancelled!');
} else {
    $form->render(['title' => 'Cancel Training Session']);
}
