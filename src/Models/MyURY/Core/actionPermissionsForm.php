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
                )));