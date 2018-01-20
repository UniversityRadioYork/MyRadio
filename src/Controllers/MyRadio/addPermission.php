<?php
/**
 * Provides a tool to add new permissions for MyRadio Service/Module/Action systems.
 */
use \MyRadio\MyRadio\AuthUtils;
use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\MyRadio\URLUtils;
use \MyRadio\MyRadio\MyRadioForm;
use \MyRadio\MyRadio\MyRadioFormField;

/*
 * Form definition for adding permissions.
 */
$form = new MyRadioForm(
    'add_permission',
    $module,
    $action,
    [
        'title' => 'Permissions',
        'subtitle' => 'New Permission',
    ]
);

$form->addField(
    new MyRadioFormField(
        'constant',
        MyRadioFormField::TYPE_TEXT,
        [
            'explanation' => 'Type a constant name (AUTH_NAME) for the permission.',
            'label' => 'Name',
            'required' => true,
        ]
    )
)->addField(
    new MyRadioFormField(
        'description',
        MyRadioFormField::TYPE_TEXT,
        [
            'explanation' => 'Type a description for what this permission allows.',
            'label' => 'Description',
            'required' => true,
        ]
    )
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //Submitted
    $data = $form->readValues();

    $constant = $data['constant'];
    if (substr($constant, 0, 5) == 'AUTH_' && strlen($constant) >= 6) {
        AuthUtils::addPermission($data['description'],$constant);
        $message = 'The permission "'. $constant .'" has been added successfully.';
        URLUtils::redirectWithMessage('MyRadio','listPermissions',$message);
    } else {
        URLUtils::backWithMessage('The permission name should be a constant starting in "AUTH_".');
    }

} else {
    //Not submitted
    $form->render();
}
