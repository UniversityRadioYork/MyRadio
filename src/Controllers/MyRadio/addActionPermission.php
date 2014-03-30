<?php
/**
 * @todo Document properly
 * @package MyRadio_Core
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 24072012
 */

require 'Models/MyRadio/actionPermissionsForm.php';
$data = $form->readValues();
require 'Models/MyRadio/addActionPermission.php';
require 'Views/MyRadio/back.php';
