<?php
/**
 * Provides a tool to manage permissions for MyURY Service/Module/Action systems
 * 
 * @version 23072012
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @package MyURY_Core
 */

/**
 * Include the current permissions. This will be rendered in a DataTable.
 */
$data = CoreUtils::getAllActionPermissions();
/**
 * Include a form definition for adding permissions.
 */
require 'Models/MyURY/Core/actionPermissionsForm.php';
/**
 * Pass it over to the actionPermissions view for output.
 */
require 'Views/MyURY/Core/actionPermissions.php';