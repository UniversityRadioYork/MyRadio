<?php
/**
 * @todo Document properly
 * @package MyURY_Core
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 24072012
 */

require 'Models/MyURY/actionPermissionsForm.php';
$data = $form->readValues();
require 'Models/MyURY/addActionPermission.php';
require 'Views/MyURY/back.php';