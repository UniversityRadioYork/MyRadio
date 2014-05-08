<?php
/**
 * Sets up the database data for a new installation of MyRadio
 *
 * @version 20140506
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @package MyRadio_Core
 * @todo Apply patches
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$mode = strtoupper($_POST['mode']);
	if (!defined('DBDATA_'.$mode)) {
		die('Invalid mode.');
	}

	$mode = constant('DBDATA_'.$mode);

	$db = Database::getInstance();
	$db->query(
		'INSERT INTO myradio.schema (attr, value) VALUES (\'datamode\', $1)',
		[$mode]
	);

	switch ($mode){
		case DBDATA_COMPLETE:
		case DBDATA_PERMISSIONS:
			foreach (json_decode(file_get_contents(SCHEMA_DIR . 'data-actions.json')) as $action) {
				$module = CoreUtils::getModuleId($action[0]);
				CoreUtils::getActionId($action[1]);
			}
			foreach (json_decode(file_get_contents(SCHEMA_DIR . 'data-auth.json')) as $auth) {
				CoreUtils::addPermission($auth[0], $auth[1]);
			}
			break;
		default:
			die('Invalid mode control sequence.');
	}
} else {
	CoreUtils::getTemplateObject()
		->setTemplate('Setup/dbdata.twig')
		->addVariable('title', 'Database Data')
		->render();
}
