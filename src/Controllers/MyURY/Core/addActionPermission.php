<?php
/**
 * @todo Document properly
 * @package MyURY_Core
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 24072012
 */

require 'Models/MyURY/Core/actionPermissionsForm.php';
$data = $form->readValues();
require 'Models/MyURY/Core/addActionPermission.php';
require 'Views/MyURY/Core/back.php';