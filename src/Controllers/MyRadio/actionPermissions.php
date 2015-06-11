<?php
/**
 * Provides a tool to manage permissions for MyRadio Service/Module/Action systems
 *
 * @package MyRadio_Core
 */

use \MyRadio\MyRadio\AuthUtils;
use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\MyRadio\URLUtils;
use \MyRadio\MyRadio\MyRadioForm;
use \MyRadio\MyRadio\MyRadioFormField;

/**
 * Form definition for adding permissions.
 */
$form = new MyRadioForm(
    'assign_action_permissions',
    $module,
    $action,
    [
        'title' => 'Assign Action Permissions'
    ]
);

$form->addField(
    new MyRadioFormField(
        'module',
        MyRadioFormField::TYPE_TEXT,
        [
            'explanation' => 'Type a Module to apply permissions to',
            'label' => 'Module'
        ]
    )
)->addField(
    new MyRadioFormField(
        'action',
        MyRadioFormField::TYPE_TEXT,
        [
            'explanation' => 'Type an Action within that Module to apply permissions to. '
                .'Leave blank to apply it to all Actions.',
            'label' => 'Action',
            'required' => false
        ]
    )
)->addField(
    new MyRadioFormField(
        'permission',
        MyRadioFormField::TYPE_SELECT,
        [
            'explanation' => 'Select a permission that you want to add which when granted '
                .'allows a user to perform this Action. These use boolean OR, not AND so may not '
                .'stack as you would like depending on circumstances. Leave blank to allow global '
                .'access.',
            'label' => 'Permission',
            'required' => false,
            'options' => array_merge(
                [
                    [
                        'value' => null,
                        'text' => 'GLOBAL ACCESS'
                    ]
                ],
                AuthUtils::getAllPermissions()
            )
        ]
    )
);


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //Submitted
    $data = $form->readValues();

    $setModule = CoreUtils::getModuleId($data['module']);
    $setAction = CoreUtils::getActionId($setModule, $data['action']);
    $permission = $data['permission'];
    if (empty($setAction)) {
        $setAction = null;
    }
    if (empty($permission)) {
        $permission = null;
    }

    AuthUtils::addActionPermission($setModule, $setAction, $permission);

    URLUtils::backWithMessage('The action permission has been updated.');

} else {
    //Not Submitted
    //Include the current permissions. This will be rendered in a DataTable.
    $data = AuthUtils::getAllActionPermissions();

    /**
     * Pass it over to the actionPermissions view for output.
     */
    for ($i = 0; $i < sizeof($data); $i++) {
        $data[$i]['del'] = [
            'display' => 'text',
            'url' => URLUtils::makeURL(
                'Core',
                'removeActionPermission',
                ['permissionid' => $data[$i]['actpermissionid']]
            ),
            'value' => 'Delete'
        ];
    }
    $form->setTemplate('MyRadio/actionPermissions.twig')
        ->render(
            [
            'tabledata' => $data,
            'tablescript' => 'myury.core.actionPermissions'
            ]
        );
}
