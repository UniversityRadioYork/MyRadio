<?php
/**
 * Sets up the database schema for MyRadio.
 */
use \MyRadio\Database;
use \MyRadio\MyRadioException;
use \MyRadio\MyRadio\CoreUtils;

try {
    Database::getInstance();
} catch (MyRadioException $e) {
    header('Location: ?c=dbserver&err='.$e->getMessage());
}

// What does the database currently look like?
$action = 'ERROR';
try {
    $result = Database::getInstance()->fetchColumn('SELECT value FROM myradio.schema WHERE attr=\'version\'');
} catch (Exception $e) {
    $result = null;
}

if (!isset($result[0])) {
    //Well, it looks like MyRadio isn't installed here.
    $version = 0;
    $operation = 'NEW';
} else {
    $version = (int) $result[0];
    if ($version < MYRADIO_CURRENT_SCHEMA_VERSION) {
        //MyRadio schema has been created, but is out of date.
        $operation = 'UPGRADE';
    } elseif ($version > MYRADIO_CURRENT_SCHEMA_VERSION) {
        //The MyRadio schema seems to be newer than the one we're expecting.
        $operation = 'NEWER_WARN';
    } elseif ($version == MYRADIO_CURRENT_SCHEMA_VERSION) {
        //Yay, nothing to do!
        $operation = 'CURRENT';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($operation) {
        case 'NEW':
            $db = Database::getInstance();
            try {
                $db->query(file_get_contents(SCHEMA_DIR.'base.sql'));
            } catch (MyRadioException $e) {
                $error = pg_last_error();
                CoreUtils::getTemplateObject()
                ->setTemplate('Setup/dbschema_error.twig')
                ->addVariable('title', 'Database Schema')
                ->addVariable('error', $error)
                ->render();
                exit;
            }

            //Force a repeat until it recognises that base.sql has been imported
            $next = '?c=dbschema';
            //Break deliberately ommitted - fallthrough to UPGRADE
        case 'UPGRADE':
            if (!isset($db)) {
                $db = Database::getInstance();
            }

            $db->query('BEGIN');
	    while ($version < MYRADIO_CURRENT_SCHEMA_VERSION) {
                ++$version;
                try {
                    $db->query(file_get_contents(SCHEMA_DIR.'patches/'.$version.'.sql'));
                    $db->query('UPDATE myradio.schema SET value='.$version.' WHERE attr=\'version\'');
                } catch (MyRadioException $e) {
                    $error = pg_last_error();
                    CoreUtils::getTemplateObject()
                    ->setTemplate('Setup/dbschema_error.twig')
                    ->addVariable('title', 'Database Schema')
                    ->addVariable('error', $error)
                    ->render();
                }
            }
            $db->query('COMMIT');
            if (!isset($next)) {
                $next = '?c=dbdata';
            }
            break;
        case 'NEWER_WARN':
        case 'CURRENT':
            $next = '?c=dbdata';
            break;
        default:
            die('Unexpected database operation.');
    }
    header('Location: '.$next);
} else {
    CoreUtils::getTemplateObject()
        ->setTemplate('Setup/dbschema.twig')
        ->addVariable('title', 'Database Schema')
        ->addVariable('operation', $operation)
        ->render();
}
