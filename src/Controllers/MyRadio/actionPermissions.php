<?php
/**
 * Provides a tool to manage permissions for MyRadio Service/Module/Action systems
 * 
 * @version 23072012
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @package MyRadio_Core
 */

/**
 * Include the current permissions. This will be rendered in a DataTable.
 */
$data = CoreUtils::getAllActionPermissions();
/**
 * Include a form definition for adding permissions.
 */
require 'Models/MyRadio/actionPermissionsForm.php';
/**
 * Pass it over to the actionPermissions view for output.
 */
require 'Views/MyRadio/actionPermissions.php';