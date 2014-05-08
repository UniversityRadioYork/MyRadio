<?php
/**
 * The setup controller is called on a new (or potentially updated)
 * installation to get things set up.
 *
 * At the moment it's quite simple, but longer term it'd be great if it could
 * enable/disable modules and default permission structures, as well as update
 * the DB schema to ensure it is current.
 *
 * @version 20140501
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @package MyRadio_Core
 */

define('SILENT_EXCEPTIONS', true);
define('DBDATA_COMPLETE', 0);
define('DBDATA_PERMISSIONS', 1);
define('DBDATA_SUDO', 2);
define('DBDATA_BLANK', 3);
define('SCHEMA_DIR', __DIR__.'/../../../schema/');

$controller = isset($_REQUEST['c']) ? $_REQUEST['c'] : 'checks';

require_once 'Models/Core/api_nodb.php';

session_start();
if (isset($_SESSION['myradio_setup_config'])) {
	$config_overrides = $_SESSION['myradio_setup_config'];
	foreach ($_SESSION['myradio_setup_config'] as $k => $v) {
		Config::$$k = $v;
	}
}
session_write_close();
register_shutdown_function(function()
{
	if (isset($GLOBALS['config_overrides'])) {
		session_start();
		$_SESSION['myradio_setup_config'] = $GLOBALS['config_overrides'];
	}
});

CoreUtils::actionSafe($controller);
require_once 'Controllers/Setup/'.$controller.'.php';
