<?php

/**
 * This is the Root Controller - it is the backbone of every request, preparing resources and passing the request onto
 * the necessary handler.
 *
 * @package MyRadio_Core
 */

use \MyRadio\MyRadio\CoreUtils;

require_once __DIR__.'/root.php';

/**
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
    } elseif (CoreUtils::isValidController($container['config']->default_module, $info[0])) {
        $module = $container['config']->default_module;
        $action = $info[0];
    } elseif (CoreUtils::isValidController($info[0], $container['config']->default_action)) {
        $module = $info[0];
        $action = $container['config']->default_action;
    } else {
        require 'Controllers/Errors/404.php';
        exit;
    }
} else {
    $module = (isset($_REQUEST['module']) ? $_REQUEST['module'] : $container['config']->default_module);
    $action = (isset($_REQUEST['action']) ? $_REQUEST['action'] : $container['config']->default_action);
    if (!CoreUtils::isValidController($module, $action)) {
        //Yep, that doesn't exist.
        require 'Controllers/Errors/404.php';
        exit;
    }
}

/**
 * Use the Database authentication data to check whether the user has permission to access that.
 * This method will automatically cause a premature exit if necessary.
 *
 * IMPORTANT: This will cause a fatal error if an action does not have any permissions associated with it.
 * This is to prevent developers from forgetting to assign permissions to an action.
 */
CoreUtils::requirePermissionAuto($module, $action);

/**
 * If a Joyride is defined, start it
 */
if (isset($_REQUEST['joyride'])) {
    $_SESSION['joyride'] = $_REQUEST['joyride'];
}

//Include the requested action
require 'Controllers/'. $module . '/' . $action . '.php';
