<?php
/**
 * Provides a tool to remove action permissions for MyRadio Service/Module/Action systems.
 */
use \MyRadio\MyRadio\AuthUtils;
use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\MyRadio\URLUtils;
use \MyRadio\MyRadio\MyRadioForm;
use \MyRadio\MyRadio\MyRadioFormField;

/*
 * Form definition for removing permission.
 */
$form = new MyRadioForm(
    'add_permission',
    $module,
    $action,
    [
        'title' => 'Permissions',
        'subtitle' => 'Delete Action Permission',
    ]
);
$form->addField(
    new MyRadioFormField(
        'permissionid',
        MyRadioFormField::TYPE_HIDDEN,
        [
            'required' => true
        ]
    )
);
$form->addField(
    new MyRadioFormField(
        'module',
        MyRadioFormField::TYPE_TEXT,
        [
            'enabled' => false
        ]
    )
);
$form->addField(
    new MyRadioFormField(
        'action',
        MyRadioFormField::TYPE_TEXT,
        [
            'enabled' => false
        ]
    )
);
$form->addField(
    new MyRadioFormField(
        'permission',
        MyRadioFormField::TYPE_TEXT,
        [
            'enabled' => false
        ]
    )
);
$form->addField(
    new MyRadioFormField(
        'confirm',
        MyRadioFormField::TYPE_CHECK,
        [
            'explanation' => '<strong>I confirm that deleting this action permission is
                             safe and won\'t break things.</strong>',
            'label' => 'Confirm Deletion',
        ]
    )
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //Submitted
    $data = $form->readValues();

    $actPermissionID = $data['permissionid'];
    $confirm = $data['confirm'];

    if ($confirm) {
        AuthUtils::removeActionPermission($actPermissionID);
        $message = 'The action permission has been removed.';
    } else {
        $message = 'The action permission has been not been altered.';
    }
    URLUtils::redirectWithMessage('MyRadio', 'actionPermissions', $message);
} else {
    //Not Submitted
    if (isset($_REQUEST['permissionid'])) {
        $actionPermission = AuthUtils::getActionPermission($_REQUEST['permissionid']);
        $form->editMode($_REQUEST['permissionid'], [
                'permissionid' => $_REQUEST['permissionid'],
                'module' => $actionPermission['module'],
                'action' => $actionPermission['action'],
                'permission' => $actionPermission['permission'],
            ]
        )->render();
    } else {
        throw new MyRadioException('An PermissionID to delete has not been provided, please try again.', 400);
    }
}
