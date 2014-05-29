<?php
/**
 * Sets up the initial admin user for MYRadio
 *
 * @version 20140515
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @package MyRadio_Core
 */

//Attempt to Database
require_once 'Classes/Database.php';
require_once 'Interfaces/CacheProvider.php';
require_once 'Classes/APCProvider.php';
require_once 'Classes/MyRadioEmail.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$user = MyRadio_User::create(
		$_REQUEST['first-name'],
		$_REQUEST['last-name'],
		null,
		$_REQUEST['gender'],
		null,
		$_REQUEST['email'],
		empty($_REQUEST['phone']) ? null : $_REQUEST['phone'],
		true,
		Config::$membership_fee,
		$_REQUEST['password']
	);

	// Give this user every possible permission
	foreach (CoreUtils::getAllPermissions() as $auth) {
		$user->grantPermission($auth['value']);
	}

	header('Location: ?c=save');
} else {
	CoreUtils::getTemplateObject()
		->setTemplate('Setup/user.twig')
		->addVariable('title', 'User')
		->addVariable('db_error', isset($_GET['err']))
		->render();
}
