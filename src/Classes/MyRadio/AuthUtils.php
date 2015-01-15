<?php

/**
 * This file provides the AuthUtils class for MyRadio
 * @package MyRadio_Core
 */

namespace MyRadio\MyRadio;

use \MyRadio\Database;
use \MyRadio\Config;

/**
 * Authentication and permission API Utilities.
 * @package MyRadio_Core
 */
class AuthUtils
{
    /**
     * This stores whether the Permissions have been defined to prevent re-defining,
     * causing errors and wasting time. Once setUpAuth is run, this is set to true
     * to prevent subsequent runs.
     * @var boolean
     */
    private static $auth_cached = false;

    /**
     * Stores permission typeid => description mappings
     * @var Array
     */
    private static $typeid_descr = [];

    /**
     * Sets up the Authentication Constants
     * @return void
     * @assert () == null
     */
    public static function setUpAuth()
    {
        if (self::$auth_cached) {
            return;
        }

        $db = Database::getInstance();
        $result = $db->fetchAll('SELECT typeid, phpconstant, descr FROM l_action');
        foreach ($result as $row) {
            define($row['phpconstant'], $row['typeid']);
            self::$typeid_descr[$row['typeid']] = $row['descr'];
        }

        self::$auth_cached = true;
    }

    /**
     * Returns the Actions and API Endpoints that utilise a given type.
     *
     * @param  int            $typeid
     * @return [[action,...], [api method,...]]
     */
    public static function getAuthUsage($typeid)
    {
        $db = Database::getInstance();
        $actions = $db->fetchAll(
            'SELECT modules.name AS module, actions.name AS action
            FROM myury.act_permission
            LEFT JOIN myury.modules USING (moduleid)
            LEFT JOIN myury.actions USING (actionid)
            WHERE typeid=$1',
            [$typeid]
        );

        $apis = $db->fetchAll(
            'SELECT api_name, method_name
            FROM myury.api_method_auth
            LEFT JOIN myury.api_class_map USING (class_name)
            WHERE typeid=$1',
            [$typeid]
        );

        return [$actions, $apis];
    }

    /**
     * Gets the description (friendly name) of the given permission.
     *
     * @param  int    $typeid
     * @return String
     */
    public static function getAuthDescription($typeid)
    {
        self::setUpAuth();

        return self::$typeid_descr[$typeid];
    }

    /**
     * Checks using cached permissions whether the current member has the specified permission
     * @param  int     $permission The ID of the permission, resolved by using an AUTH_ constant
     * @return boolean Whether the member has the requested permission
     */
    public static function hasPermission($permission)
    {
        if (!isset($_SESSION['member_permissions'])) {
            return false;
        }
        if ($permission === null) {
            return true;
        }

        return in_array($permission, $_SESSION['member_permissions']);
    }

    /**
     * Checks if the user has the given permission. Or, alternatiely, if we are currently running CLI, reutrns true.
     * @param  int  $permission A permission constant to check
     * @return void Will Fatal error if the user does not have the permission
     */
    public static function requirePermission($permission)
    {
        if (php_sapi_name() === 'cli') {
            return true; //Non-interactive version has God Rights.
        }
        if (!self::hasPermission($permission)) {
            //Load the 403 controller and exit
            require 'Controllers/Errors/403.php';
            exit;
        }
    }

    /**
     * Checks if the user has the given permissions required for the given Module/Action combination
     *
     * The query needs a little bit of explaining.<br>
     * The first two WHERE clauses just set up foreign key references - we're searching by name, not ID.<br>
     * The next two WHERE clauses return exact or wildcard matches for this Module/Action combination.<br>
     * The final two AND NOT phrases make sure it ignores wildcards that allow any access.
     *
     * @param  String $module  The Module to check permissions for
     * @param  String $action  The Action to check permissions for
     * @param  bool   $require If true, will die if the user does not have permission. If false, will just return false
     * @return bool   True on required or authorised, false on unauthorised
     */
    public static function requirePermissionAuto($module, $action, $require = true)
    {
        self::setUpAuth();
        $db = Database::getInstance();

        $result = $db->fetchColumn(
            'SELECT typeid FROM myury.act_permission
            LEFT OUTER JOIN myury.modules ON act_permission.moduleid=modules.moduleid
            LEFT OUTER JOIN myury.actions ON act_permission.actionid=actions.actionid
            WHERE (myury.modules.name=$1 OR myury.act_permission.moduleid IS NULL)
            AND (myury.actions.name=$2 OR myury.act_permission.actionid IS NULL)
            AND NOT (myury.act_permission.actionid IS NULL AND myury.act_permission.typeid IS NULL)
            AND NOT (myury.act_permission.moduleid IS NULL AND myury.act_permission.typeid IS NULL)',
            [$module, $action]
        );

        //Don't allow empty result sets - throw an Exception as this is very very bad.
        if (empty($result) && $require) {
            throw new MyRadioException('There are no permissions defined for the ' . $module . '/' . $action . ' action!');
        }

        $authorised = false;
        foreach ($result as $permission) {
            //It only needs to match one
            if ($permission === AUTH_NOLOGIN || (self::hasPermission($permission) && $_SESSION['auth_use_locked'] === false)) {
                $authorised = true;
                break;
            }
        }

        if (!$authorised && $require) {
            //Requires login
            if (!isset($_SESSION['memberid']) || $_SESSION['auth_use_locked'] !== false) {
                URLUtils::redirect('MyRadio', 'login', ['next' => $_SERVER['REQUEST_URI']]);
            } else {
                //Authenticated, but not authorized
                require 'Controllers/Errors/403.php';
            }
            exit;
        }

        //Return true on required success, or whether authorised otherwise
        return $require || $authorised;
    }

    /**
     * Returns a list of all currently defined permissions on MyRadio Service/Module/Action combinations.
     *
     * This has multiple UNIONS with similar queries so it gracefully deals with NULL values - the joins lose them.
     *
     * @todo Is there a nicer way of doing this?
     * @todo Won't do null fields. Requires outer joins.
     *
     * @return Array A 2D Array, where each second dimensions is as follows:<br>
     *               action: The name of the Action page<br>
     *               module: The name of the Module the action is in<br>
     *               service: The name of the Service the module is in<br>
     *               permission: The name of the permission applied to that Service/Module/Action combination<br>
     *               actpermissionid: The unique ID of this Service/Module/Action combination
     *
     */
    public static function getAllActionPermissions()
    {
        return Database::getInstance()->fetchAll(
            'SELECT actpermissionid,
            myury.services.name AS service,
            myury.modules.name AS module,
            myury.actions.name AS action,
            public.l_action.descr AS permission
            FROM myury.act_permission, myury.services, myury.modules, myury.actions, public.l_action
            WHERE myury.act_permission.actionid=myury.actions.actionid
            AND myury.act_permission.moduleid=myury.modules.moduleid
            AND myury.act_permission.serviceid=myury.services.serviceid
            AND myury.act_permission.typeid = public.l_action.typeid

            UNION

            SELECT actpermissionid,
            myury.services.name AS service,
            myury.modules.name AS module,
            \'ALL ACTIONS\' AS action,
            public.l_action.descr AS permission
            FROM myury.act_permission, myury.services, myury.modules, public.l_action
            WHERE myury.act_permission.moduleid=myury.modules.moduleid
            AND myury.act_permission.serviceid=myury.services.serviceid
            AND myury.act_permission.typeid = public.l_action.typeid
            AND myury.act_permission.actionid IS NULL

            UNION

            SELECT actpermissionid,
            myury.services.name AS service,
            myury.modules.name AS module,
            myury.actions.name AS action,
            \'GLOBAL ACCESS\' AS permission
            FROM myury.act_permission, myury.services, myury.modules, myury.actions
            WHERE myury.act_permission.moduleid=myury.modules.moduleid
            AND myury.act_permission.serviceid=myury.services.serviceid
            AND myury.act_permission.actionid=myury.actions.actionid
            AND myury.act_permission.typeid IS NULL

            ORDER BY service, module'
        );
    }

    /**
     * Returns a list of Permissions ready for direct use in a select MyRadioFormField
     * @return Array A 2D Array matching the MyRadioFormField::TYPE_SELECT specification.
     */
    public static function getAllPermissions()
    {
        return Database::getInstance()->fetchAll(
            'SELECT typeid AS value, descr AS text FROM public.l_action
            ORDER BY descr ASC'
        );
    }

    /**
     * Add a new permission constant to the database.
     * @param String $descr A useful friendly description of what this action means.
     * @param String $constant /AUTH_[A-Z_]+/
     */
    public static function addPermission($descr, $constant)
    {
        $value = (int)Database::getInstance()->fetchColumn(
            'INSERT INTO public.l_action (descr, phpconstant)
            VALUES ($1, $2) RETURNING typeid',
            [$descr, $constant]
        )[0];
        define($constant, $value);
        return $value;
    }

    /**
     * Assigns a permission to a command. Note arguments are the integer IDs
     * NOT the String names
     *
     * @param int $module The module ID
     * @param int $action The action ID
     * @param int $permission The permission typeid
     */
    public static function addActionPermission($module, $action, $permission)
    {
        Database::getInstance()->query(
            'INSERT INTO myury.act_permission (serviceid, moduleid, actionid, typeid)
            VALUES ($1, $2, $3, $4)',
            [Config::$service_id, $module, $action, $permission]
        );
    }
}

