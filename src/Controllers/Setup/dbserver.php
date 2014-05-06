<?php
/**
 * Sets up the database connection for MyRadio
 *
 * @version 20140504
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @package MyRadio_Core
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$config_overrides['db_hostname'] = $_POST['hostname'];
	$config_overrides['db_name'] = $_POST['dbname'];
	$config_overrides['db_user'] = $_POST['username'];
	$config_overrides['db_pass'] = $_POST['password'];
	header('Location: ?c=dbschema');
} else {
	CoreUtils::getTemplateObject()
		->setTemplate('Setup/dbserver.twig')
		->addVariable('title', 'Database Server')
		->addVariable('db_error', isset($_GET['err']))
		->render();
}