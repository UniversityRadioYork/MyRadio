<?php
/**
 * Sets up the database connection for MyRadio.
 */
use \MyRadio\Database;
use \MyRadio\MyRadioException;
use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\Config;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $config_overrides['db_hostname'] = $_POST['hostname'];
    $config_overrides['db_name'] = $_POST['dbname'];
    $config_overrides['db_user'] = $_POST['username'];
    $config_overrides['db_pass'] = $_POST['password'];

    // Apply these configs now, they'll be made permanant in the setup root.php on next page.
    foreach ($config_overrides as $k => $v) {
        Config::$$k = $v;
    }

    //Test a DB connection
    try {
        $handle = Database::getInstance();
    } catch (MyRadioException $e) {
        header('Location: ?c=dbserver&db_error=true');
        exit; //prevent further execution
    }
    //else
    header('Location: ?c=dbschema');
} else {
    CoreUtils::getTemplateObject()
        ->setTemplate('Setup/dbserver.twig')
        ->addVariable('title', 'Database Server')
        ->addVariable('db_error', isset($_GET['db_error']))
        ->render();
}
