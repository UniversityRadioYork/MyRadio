<?php

/**
 * Render the Action Permissions management page
 *
 * @version 24072012
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @package MyRadio_Config
 */
for ($i = 0; $i < sizeof($data); $i++) {
    $data[$i]['del'] = [
        'display' => 'text',
        'url' => CoreUtils::makeURL(
            'Core',
            'removeActionPermission',
            ['permissionid' => $data[$i]['actpermissionid']]
        ),
        'value' => 'Delete'
    ];
}
$form->setTemplate('MyRadio/actionPermissions.twig')
    ->render([
        'tabledata' => $data,
        'tablescript' => 'myury.core.actionPermissions'
    ]);
