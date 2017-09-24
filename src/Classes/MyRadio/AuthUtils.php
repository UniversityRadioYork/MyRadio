<?php

/**
 * This file provides the AuthUtils class for MyRadio.
 */
namespace MyRadio\MyRadio;

use ReCaptcha\ReCaptcha;
use MyRadio\Config;
use MyRadio\Database;
use MyRadio\MyRadioException;
use MyRadio\ServiceAPI\MyRadio_User;

/**
 * Authentication and permission API utilities.
 */
class AuthUtils
{
    /**
     * This stores whether the Permissions have been defined to prevent re-defining, causing errors and wasting time
     * Once setUpAuth is run, this is set to true to prevent subsequent runs.
     *
     * @var bool
     */
    private static $auth_cached = false;

    /**
     * Stores permission typeid => description mappings.
     *
     * @var array
     */
    private static $typeid_descr = [];

    /**
     * Sets up the Authentication Constants.
     *
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
     * @param int $typeid
     *
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
     * @param int $typeid
     *
     * @return string
     */
    public static function getAuthDescription($typeid)
    {
        self::setUpAuth();

        return self::$typeid_descr[$typeid];
    }

    /**
     * Checks using cached permissions whether the current member has the specified permission.
     *
     * @param int $permission The ID of the permission, resolved by using an AUTH_ constant
     *
     * @return bool Whether the member has the requested permission
     */
    public static function hasPermission($permission)
    {
        if (isset($_SESSION['memberid'])) {
            return MyRadio_User::getInstance()->hasAuth($permission);
        } else {
            return $permission === null;
        }
    }

    /**
     * Checks if the user has the given permission. Or, alternatiely, if we are currently running CLI, reutrns true.
     *
     * @param int $permission A permission constant to check
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
     * Checks if the user has the given permissions required for the given Module/Action combination.
     *
     * The query needs a little bit of explaining.<br>
     * The first two WHERE clauses just set up foreign key references - we're searching by name, not ID.<br>
     * The next two WHERE clauses return exact or wildcard matches for this Module/Action combination.<br>
     * The final two AND NOT phrases make sure it ignores wildcards that allow any access.
     *
     * @param string $module  The Module to check permissions for
     * @param string $action  The Action to check permissions for
     * @param bool   $require If true, will die if the user does not have permission. If false, will just return false
     *
     * @return bool True on required or authorised, false on unauthorised
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
            throw new MyRadioException('There are no permissions defined for the '.$module.'/'.$action.' action!');
        }

        $authorised = false;
        foreach ($result as $permission) {
            //It only needs to match one
            if ($permission === AUTH_NOLOGIN
                || (self::hasPermission($permission)
                    && isset($_SESSION['auth_use_locked'])
                    && $_SESSION['auth_use_locked'] === false)
            ) {
                $authorised = true;
                break;
            }
        }

        if (!$authorised && $require) {
            //Requires login
            if (!isset($_SESSION['memberid']) || (isset($_SESSION['auth_use_locked'])
                                                  && $_SESSION['auth_use_locked'] !== false)) {
                $is_ajax = (isset($_SERVER['HTTP_X_REQUESTED_WITH'])
                                && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
                            || empty($_SERVER['REMOTE_ADDR']);
                if ($is_ajax) {
                    throw new MyRadioException('Login required', 401);
                } else {
                    URLUtils::redirect('MyRadio', 'login', ['next' => $_SERVER['REQUEST_URI']]);
                }
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
     * @return array A 2D Array, where each second dimensions is as follows:<br>
     *               action: The name of the Action page<br>
     *               module: The name of the Module the action is in<br>
     *               service: The name of the Service the module is in<br>
     *               permission: The name of the permission applied to that Service/Module/Action combination<br>
     *               actpermissionid: The unique ID of this Service/Module/Action combination
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
     * Returns a list of Permissions ready for direct use in a select MyRadioFormField.
     *
     * @return array A 2D Array matching the MyRadioFormField::TYPE_SELECT specification.
     */
    public static function getAllPermissions()
    {
        return Database::getInstance()->fetchAll(
            'SELECT typeid AS value, descr AS text FROM public.l_action
            ORDER BY descr ASC'
        );
    }

    /**
     * udiff function for permission value equality.
     *
     * @param array $perm1 permission value & description
     * @param array $perm2 permission value & description
     *
     * @return int comparison result
     */
    private static function comparePermission($perm1, $perm2)
    {
        if ($perm1['value'] === $perm2['value']) {
            return 0;
        } elseif ($perm1['value'] < $perm2['value']) {
            return -1;
        } else {
            return 1;
        }
    }

    /**
     * Returns all permissions that are in $perms but not $diffPerms.
     *
     * @param   $perms     array of permissions
     * @param   $diffPerms array of permissions
     *
     * @return array all permissions not included in $perms
     */
    public static function diffPermissions($perms, $diffPerms)
    {
        return array_udiff($perms, $diffPerms, 'self::comparePermission');
    }

    /**
     * Add a new permission constant to the database.
     *
     * @param string $descr    A useful friendly description of what this action means.
     * @param string $constant /AUTH_[A-Z_]+/
     */
    public static function addPermission($descr, $constant)
    {
        $value = (int) Database::getInstance()->fetchColumn(
            'INSERT INTO public.l_action (descr, phpconstant)
            VALUES ($1, $2) RETURNING typeid',
            [$descr, $constant]
        )[0];
        define($constant, $value);

        return $value;
    }

    /**
     * Assigns a permission to a command. Note arguments are the integer IDs
     * NOT the String names.
     *
     * @param int $module     The module ID
     * @param int $action     The action ID
     * @param int $permission The permission typeid
     */
    public static function addActionPermission($module, $action, $permission)
    {
        $db = Database::getInstance();
        $db->query(
            'INSERT INTO myury.act_permission (serviceid, moduleid, actionid, typeid)
            VALUES ($1, $2, $3, $4)',
            [Config::$service_id, $module, $action, $permission]
        );
    }

    /**
     * Tests whether the given username or password are valid against a provider
     * (and the right provider if needed).
     *
     * This is a far more basic version of the full Controllers/login.php system,
     * not verifying if the user needs to take an action first.
     * It does, however, update the User's last login time..
     * You MUST use POST with this - otherwise the credentials will turn up in
     * access logs.
     *
     * @param string $user
     * @param string $pass
     *
     * @return MyRadio_User|false
     *
     * @api    POST
     */
    public static function testCredentials($user, $pass)
    {
        //Make a best guess at the user account
        //This way we can skip authenticators if they have one set
        $u = MyRadio_User::findByEmail($user);
        if ($u instanceof MyRadio_User && $u->getAuthProvider() !== null) {
            $authenticators = [$u->getAuthProvider()];
        } else {
            $authenticators = Config::$authenticators;
        }

        //Iterate over each authenticator
        foreach ($authenticators as $authenticator) {
            $a = new $authenticator();
            $result = $a->validateCredentials($user, $pass);
            if ($result instanceof MyRadio_User) {
                if (Config::$single_authenticator
                    && $result->getAuthProvider() !== null
                    && $result->getAuthProvider() !== $authenticator
                ) {
                    //This is the wrong authenticator for the user
                    continue;
                } else {
                    $result->updateLastLogin();

                    return $result;
                }
            }
        }

        return false;
    }

    /**
     * Verify that the recaptcha response is valid.
     *
     * @param string $response g-recaptcha-response from the widget
     * @param string $addr     Remote IP address of response
     *
     * @return Bool/Array true if valid, array of errors if not
     */
    public static function verifyRecaptcha($response, $addr = null)
    {
        $recaptcha = new ReCaptcha(Config::$recaptcha_private_key);

        $resp = $recaptcha->verify($response, $addr);

        if ($resp->isSuccess()) {
            return true;
        } else {
            return $resp->getErrorCodes();
        }
    }
}
