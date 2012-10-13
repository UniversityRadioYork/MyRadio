<?php

/**
 * 
 * @todo Proper Documentation
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 24072012
 * @package MyURY_Core
 */
$form = new MyURYForm('assign_action_permissions', $module, 'addActionPermission',
                array(
                    'debug' => true,
                    'title' => 'Assign Action Permissions'
        ));

$form->addField(
                new MyURYFormField('service', MyURYFormField::TYPE_SELECT,
                        array(
                            'options' => CoreUtils::getServices(),
                            'explanation' => 'Select a Service to apply permissions to',
                            'label' => 'Service'
                )))
        ->addField(
                new MyURYFormField('module', MyURYFormField::TYPE_TEXT,
                        array(
                            'explanation' => 'Type a Module to apply permissions to',
                            'label' => 'Module'
                ))
        )
        ->addField(
                new MyURYFormField('action', MyURYFormField::TYPE_TEXT,
                        array(
                            'explanation' => 'Type an Action within that Module to apply permissions to.
                                      Leave blank to apply it to all Actions.',
                            'label' => 'Action',
                            'required' => false
                )))
        ->addField(
                new MyURYFormField('permission', MyURYFormField::TYPE_SELECT,
                        array(
                            'explanation' => 'Select a permission that you want to add which when granted
                                      allows a user to perform this Action. These use boolean OR, not AND so may not
                                      stack as you would like depending on circumstances. Leave blank to allow global
                                      access.',
                            'label' => 'Permission',
                            'required' => false,
                            'options' => CoreUtils::getAllPermissions()
                )));