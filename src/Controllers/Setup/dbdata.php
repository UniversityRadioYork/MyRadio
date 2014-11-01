<?php
/**
 * Sets up the database data for a new installation of MyRadio
 *
 * @version 20140506
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @package MyRadio_Core
 * @todo Apply patches
 * @todo there's some duplication in setUpFullActionsAuth and DBDATA_BLANK
 */
use \MyRadio\Database;
use \MyRadio\MyRadioException;
use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\ServiceAPI\ServiceAPI;
use \MyRadio\ServiceAPI\MyRadio_Officer;
use \MyRadio\ServiceAPI\MyRadio_Team;

//Make sure ServiceAPI has Database and Cache connections
ServiceAPI::wakeup();

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

	$warnings = [];

	/**
	 * For some reason, after a break; a further match of the same
	 * clause, so to avoid code duplication full actionauth is a function
	 */
	function setUpFullActionsAuth() {
		foreach (json_decode(file_get_contents(SCHEMA_DIR . 'data-actions.json')) as $action) {
			//The getXxxId method creates these if they don't exist
			$module = CoreUtils::getModuleId($action[0]);
			CoreUtils::getActionId($module, $action[1]);
		}
		foreach (json_decode(file_get_contents(SCHEMA_DIR . 'data-auth.json')) as $auth) {
			try {
				CoreUtils::addPermission($auth[0], $auth[1]);
			} catch (MyRadioException $e) {
				$warnings[] = 'Failed to create Permission "' . $auth[0] . '". It may already exist.';
			}
		}
		foreach (json_decode(file_get_contents(SCHEMA_DIR . 'data-actionsauth.json')) as $actionauth) {
			if (!$actionauth[3]) {
				continue;
			}
			$module = CoreUtils::getModuleId($actionauth[0]);
			$action = $actionauth[1] == null ? null : CoreUtils::getActionId($module, $actionauth[1]);
			$auth = $actionauth[2] == null ? null : constant($actionauth[2]);
			CoreUtils::addActionPermission($module, $action, $auth);
		}
	}

	switch ($mode) {
		case DBDATA_PERMISSIONS:
			setUpFullActionsAuth();
			break;
		case DBDATA_COMPLETE:
			setUpFullActionsAuth();
			$data = json_decode(file_get_contents(SCHEMA_DIR . 'data-officers.json'), true);
			foreach ($data['teams'] as $team) {
				$oTeam = MyRadio_Team::createTeam($team[0], $team[1], $team[2], $team[3]);
				foreach ($team[4] as $officer) {
					MyRadio_Officer::createOfficer(
						$officer[0],
						$officer[1],
						$officer[2],
						$officer[3],
						$oTeam,
						$officer[4]
					);
				}
			}
			break;
		case DBDATA_SUDO:
			foreach (json_decode(file_get_contents(SCHEMA_DIR . 'data-actions.json')) as $action) {
				if (in_array($action[1], ['actionPermissions', 'addActionPermission', 'listPermissions']) !== false) {
					//Skip permissions controls
					continue;
				}
				//The getXxxId method creates an ID if they don't exist
				$module = CoreUtils::getModuleId($action[0]);
				$action = CoreUtils::getActionId($module, $action[1]);
				CoreUtils::addActionPermission($module, $action, null);
			}
			break;
		case DBDATA_BLANK:
			foreach (json_decode(file_get_contents(SCHEMA_DIR . 'data-actions-min.json')) as $action) {
				//The getXxxId method create these if they don't exist
				$module = CoreUtils::getModuleId($action[0]);
				CoreUtils::getActionId($module, $action[1]);
			}
			foreach (json_decode(file_get_contents(SCHEMA_DIR . 'data-auth-min.json')) as $auth) {
				try {
					CoreUtils::addPermission($auth[0], $auth[1]);
				} catch (MyRadioException $e) {
					$warnings[] = 'Failed to create Permission "'.$auth[0].'". It may already exist.';
				}
			}
			foreach (json_decode(file_get_contents(SCHEMA_DIR . 'data-actionsauth-min.json')) as $actionauth) {
				$module = CoreUtils::getModuleId($actionauth[0]);
				$action = $actionauth[1] == null ? null : CoreUtils::getActionId($module, $actionauth[1]);
				$auth = $actionauth[2] == null ? null : constant($actionauth[2]);
				CoreUtils::addActionPermission($module, $action, $auth);
			}
			break;
		default:
			die('Invalid mode control sequence.');
	}

	if (!empty($warnings)) {
		CoreUtils::getTemplateObject()
			->setTemplate('Setup/dbdata_warning.twig')
			->addVariable('title', 'Database Data')
			->addVariable('warnings', $warnings)
			->addVariable('next', 'strings')
			->render();
	} else {
		header('Location: ?c=strings');
	}
} else {
	CoreUtils::getTemplateObject()
		->setTemplate('Setup/dbdata.twig')
		->addVariable('title', 'Database Data')
		->render();
}
