<?php

/**
 *
 * @todo Proper Documentation
 * @package MyRadio_Core
 */
$form = new MyRadioForm(
    'assign_action_permissions',
    $module,
    'addActionPermission',
    [
        'debug' => true,
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
                CoreUtils::getAllPermissions()
            )
        ]
    )
);
