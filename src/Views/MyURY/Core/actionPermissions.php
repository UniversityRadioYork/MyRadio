<?php

/**
 * Render the Action Permissions management page
 * 
 * @todo Make delete button degrade gracefully?
 * @version 24072012
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @package URY_Core
 */
for ($i = 0; $i < sizeof($data); $i++) {
  $data[$i]['del'] = '<a href="javascript:" class="actionpermissions-delete-link">Delete</a>';
}
$form->setTemplate('MyURY/actionPermissions.twig')
        ->render(array(
            'tabledata' => $data,
            'tablescript' => 'myury.core.actionPermissions'
        ));