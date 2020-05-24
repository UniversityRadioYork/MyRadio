<?php

/**
 * This is the Root Controller - it is the backbone of every request, preparing resources and passing the request onto
 * the necessary handler.
 */
use \MyRadio\Config;
use MyRadio\Database;
use \MyRadio\MyRadio\AuthUtils;
use \MyRadio\MyRadio\CoreUtils;

require_once __DIR__.'/root.php';

/*
 * Set up the Module and Action global variables. These are used by Module/Action controllers as well as this file.
 * Notice how the default Module is MyRadio. This is basically the MyRadio Menu, and maybe a couple of admin pages.
 * Notice how the default Action is 'default'. This means that the "default" Controller should exist for all Modules.
 * The top half deals with Rewritten URLs, which get mapped to ?request=
 */
if (isset($_REQUEST['request'])) {
    $info = explode('/', $_REQUEST['request']);
    //If both are defined, it's Module/Action
    if (!empty($info[1])) {
        $module = $info[0];
        $action = $info[1];
        //If there's only one, determine if it's the module or action
    } elseif (CoreUtils::isValidController(Config::$default_module, $info[0])) {
        $module = Config::$default_module;
        $action = $info[0];
    } elseif (CoreUtils::isValidController($info[0], Config::$default_action)) {
        $module = $info[0];
        $action = Config::$default_action;
    } else {
        require 'Controllers/Errors/404.php';
        exit;
    }
} else {
    $module = (isset($_REQUEST['module']) ? $_REQUEST['module'] : Config::$default_module);
    $action = (isset($_REQUEST['action']) ? $_REQUEST['action'] : Config::$default_action);
    if (!CoreUtils::isValidController($module, $action)) {
        //Yep, that doesn't exist.
        require 'Controllers/Errors/404.php';
        exit;
    }
}

// Check for maintenance
// First on all of MyRadio
if (Config::$maintenance_modules === '*') {
    require 'Controllers/Errors/Maintenance.php';
    exit;
}
// Then on the whole module
if (key_exists($module, Config::$maintenance_modules)) {
    if (Config::$maintenance_modules[$module] === '*') {
        require 'Controllers/Errors/Maintenance.php';
        exit;
    }
    // Then on the action
    if (in_array($action, Config::$maintenance_modules[$module])) {
        require 'Controllers/Errors/Maintenance.php';
        exit;
    }
}

/*
 * Use the Database authentication data to check whether the user has permission to access that.
 * This method will automatically cause a premature exit if necessary.
 *
 * IMPORTANT: This will cause a fatal error if an action does not have any permissions associated with it.
 * This is to prevent developers from forgetting to assign permissions to an action.
 */
AuthUtils::requirePermissionAuto($module, $action);

/*
 * If a Joyride is defined, start it
 */
if (isset($_REQUEST['joyride'])) {
    $_SESSION['joyride'] = $_REQUEST['joyride'];
}

// Apply analytics
if (Config::$enable_analytics) {
    if (substr($action, 0, 2) !== 'a-' // pseudo-API
        && $action !== 'config.js'
        && !($module === 'SIS' && $action === 'remote')
    ) {
        Database::getInstance()->query(
            'SELECT myradio.create_analytics_record($1, $2, $3, $4)',
            [
                $module . '/' . $action,
                $_GET['ref'] ?? '',
                $_SESSION['memberid'] ?? -1,
                session_id()
            ]
        );
    }
}

//Include the requested action
require 'Controllers/'.$module.'/'.$action.'.php';
