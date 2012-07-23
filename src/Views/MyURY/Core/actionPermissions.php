<?php

/**
 * Render the Action Permissions management page
 * 
 * @version 24072012
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @package URY_Core
 */
$form->setTemplate('MyURY/actionPermissions.twig')
        ->render(array(
            'tabledata' => $data,
            'tablescript' => 'myury.core.actionPermissions'
        ));