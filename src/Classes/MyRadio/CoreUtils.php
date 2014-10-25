<?php

/**
 * This file provides the CoreUtils class for MyRadio
 * @package MyRadio_Core
 */

namespace MyRadio\MyRadio;

use \MyRadio\Config;
use \MyRadio\Database;
use \MyRadio\MyRadioTwig;
use \MyRadio\MyRadioException, \MyRadio\MyRadioError;
use \MyRadio\ServiceAPI\MyRadio_User;
use \MyRadio\Iface\MyRadio_DataSource;


/**
 * Standard API Utilities. Basically miscellaneous functions for the core system
 * No database accessing etc should be setup here.
 *
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20140102
 * @package MyRadio_Core
 * @todo Factor out permission code into a seperate class?
 */
class CoreUtils
{
    /**
     * This stores whether the Permissions have been defined to prevent re-defining, causing errors and wasting time
     * Once setUpAuth is run, this is set to true to prevent subsequent runs
     * @var boolean
     */
    private static $auth_cached = false;
    private static $svc_version_cache = [];

    /**
     * Stores the result of CoreUtils::getAcademicYear
     *
     * This cut 8k queries off of loading one test page...
     *
     * @var int
     */
    private static $academicYear;

    /**
     * Stores permission typeid => description mappings
     * @var Array
     */
    private static $typeid_descr = [];

    /**
     * Stores actionid => uri mappings of custom web addresses (e.g. /myury/iTones/default gets mapped to /itones)
     * @var Array
     */
    private static $custom_uris = [];

    /**
     * Stores module name => id mappings to reduce query load - they are initialised once and stored
     * @var Array
     */
    private static $module_ids = [];

    /**
     * Stores action name => id mappings to reduce query load - they are initialised once and stored
     * @var Array
     */
    private static $action_ids = [];

    /**
     * Checks whether a given Module/Action combination is valid
     * @param  String  $module The module to check
     * @param  String  $action The action to check. Default 'default'
     * @return boolean Whether or not the request is valid
     * @assert ('Core', 'default') === true
     * @assert ('foo', 'barthatdoesnotandwillnoteverexisteverbecauseitwouldbesilly') === false
     * @assert ('../foo', 'bar') === false
     * @assert ('foo', '../bar') === false
     */
    public static function isValidController($module, $action = null)
    {
        if ($action === null) {
            $action = Config::$default_action;
        }
        try {
            self::actionSafe($action);
            self::actionSafe($module);
        } catch (MyRadioException $e) {
            return false;
        }
        /**
         * This is better than file_exists because it ensures that the response is valid for a version which has the file
         * when live does not
         */

        return is_string(stream_resolve_include_path('Controllers/' . $module . '/' . $action . '.php'));
    }

    /**
     * Provides a template engine object compliant with TemplateEngine interface
     * @return MyRadioTwig
     * @todo Make this generalisable for drop-in template engine replacements
     * @assert () !== false
     * @assert () !== null
     */
    public static function getTemplateObject()
    {
        require_once 'Twig/Autoloader.php';
        \Twig_Autoloader::register();

        return new MyRadioTwig();
    }

    /**
     * Checks whether a requested action is safe
     * @param  String           $action A module action
     * @return boolean          Whether the module is safe to be used on a filesystem
     * @throws MyRadioException Thrown if directory traversal detected
     * @assert ('safe!') === true
     * @assert ('../notsafe!') throws MyRadioException
     */
    public static function actionSafe($action)
    {
        if (strpos($action, '/') !== false) {
            //Someone is trying to traverse directories
            throw new MyRadioException('Directory Traversal Thrwated');
        }

        return true;
    }

    /**
     * Formats pretty much anything into a happy, human readable date/time
     * @param  string $timestring Some form of time
     * @param  bool   $time       Whether to include Hours,Mins. Default yes
     * @return String A happy time
     * @assert (40000) == '01/01/1970'
     */
    public static function happyTime($timestring, $time = true, $date = true)
    {
        return date(($date ? 'd/m/Y' : '') . ($time && $date ? ' ' : '') . ($time ? 'H:i' : ''), is_numeric($timestring) ? $timestring : strtotime($timestring));
    }

    /**
     * Formats a number into h:m:s format.
     * @param  int    $int
     * @return String
     */
    public static function intToTime($int)
    {
        $hours = floor($int / 3600);
        if ($hours === 0) {
            $hours = null;
        } else {
            $hours = $hours . ':';
        }

        $mins = floor(($int - ($hours * 3600)) / 60);
        $secs = ($int - ($hours * 3600) - ($mins * 60));

        return "$hours$mins:$secs";
    }

    /**
     * Returns a postgresql-formatted timestamp
     * @param  int    $time The time to get the timestamp for. Default right now.
     * @return String a timestamp
     * @assert (30) == '1970-01-01 00:00:30'
     */
    public static function getTimestamp($time = null)
    {
        if ($time === null) {
            $time = time();
        }

        return gmdate('Y-m-d H:i:s+00', $time);
    }

    /**
     * Gives you the starting year of the current academic year
     * @return int year
     * @assert () == 2013
     */
    public static function getAcademicYear()
    {
        if (empty(self::$academicYear)) {
            $term = Database::getInstance()->fetchColumn(
                'SELECT start FROM public.terms WHERE descr=\'Autumn\'
                AND EXTRACT(year FROM start) = $1',
                [date('Y')]
            );
            if (strtotime($term[0]) <= strtotime('+' . Config::$account_expiry_before . ' days')) {
                self::$academicYear = date('Y');
            } else {
                self::$academicYear = date('Y') - 1;
            }
        }

        return self::$academicYear;
    }

    /**
     * Returns a postgresql formatted interval
     * @param  int    $start The start time
     * @param  int    $end   The end time
     * @return String a PgSQL valid interval value
     * @assert (0, 0) == '0 seconds'
     */
    public static function makeInterval($start, $end)
    {
        return $end - $start . ' seconds';
    }

    /**
     * Redirects back to previous page.
     *
     */
    public static function back()
    {
        header('Location: '.$_SERVER['HTTP_REFERER']);
    }

    /**
     * Responds with nocontent.
     *
     */
    public static function nocontent()
    {
        header('HTTP/1.1 204 No Content');
        exit;
    }

    /**
     * Responds with JSON data.
     *
     */
    public static function dataToJSON($data)
    {
        header('Content-Type: application/json');
        header('HTTP/1.1 200 OK');

        //Decode to datasource if needed
        $data = self::dataSourceParser($data);

        if (!empty(MyRadioError::$php_errorlist)) {
            $data['myury_errors'] = MyRadioError::$php_errorlist;
        }

        echo json_encode($data);
        exit;
    }

    /**
     * Redirects to another page.
     *
     * @param  string $module The module to which we should redirect.
     * @param  string $action The optional action inside the module to target.
     * @param  array  $params Additional GET variables
     * @return null   Nothing.
     */
    public static function redirect($module, $action = null, $params = [])
    {
        header('Location: ' . self::makeURL($module, $action, $params));
    }

    /**
     * Builds a module/action URL
     * @param  string $module
     * @param  string $action
     * @param  array  $params Additional GET variables
     * @return String URL to Module/Action
     */
    public static function makeURL($module, $action = null, $params = [])
    {
        if (empty(self::$custom_uris)) {
            $result = Database::getInstance()->fetchAll('SELECT actionid, custom_uri FROM myury.actions');

            foreach ($result as $row) {
                self::$custom_uris[$row['actionid']] = $row['custom_uri'];
            }
        }
        //Check if there is a custom URL configured
        $key = self::getActionId(self::getModuleId($module), empty($action) ? Config::$default_action : $action);
        if (!empty(self::$custom_uris[$key])) {
            return self::$custom_uris[$key];
        }

        if (Config::$rewrite_url) {
            $str = Config::$base_url . $module . '/' . (($action !== null) ? $action . '/' : '');
            if (!empty($params)) {
                if (is_string($params)) {
                    if (substr($params, 0, 1) !== '?') {
                        $str .= '?';
                    }
                    $str .= $params;
                } else {
                    $str .= '?';
                    foreach ($params as $k => $v) {
                        $str .= "$k=$v&";
                    }
                    $str = substr($str, 0, -1);
                }
            }
        } else {
            $str = Config::$base_url . '?module=' . $module . (($action !== null) ? '&action=' . $action : '');

            if (!empty($params)) {
                if (is_string($params)) {
                    $str .= $params;
                } else {
                    foreach ($params as $k => $v) {
                        $str .= "&$k=$v";
                    }
                }
            }
        }

        return $str;
    }

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
        if (empty($result)) {
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
                self::redirect('MyRadio', 'login', ['next' => $_SERVER['REQUEST_URI']]);
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
     * Returns a list of all MyRadio managed Services in a 2D Array.
     * @return Array A 2D Array with each second dimension as follows:<br>
     *               value: The ID of the Service
     *               text: The Text ID of the Service
     *               enabeld: Whether the Service is enabled
     */
    public static function getServices()
    {
        return Database::getInstance()->fetchAll(
            'SELECT serviceid AS value, name AS text, enabled
            FROM myury.services ORDER BY name ASC'
        );
    }

    /**
     * A simple debug method that only displays output for a specific user.
     * @param int    $userid  The ID of the user to display for
     * @param String $message The HTML to display for this user
     * @assert (7449, 'Test') == null
     */
    public static function debugFor($userid, $message)
    {
        if ($_SESSION['memberid'] == $userid) {
            echo '<p>' . $message . '</p>';
        }
    }

    /**
     * Returns the ID of a Module, creating it if necessary
     *
     * This method first caches all module IDs, if they aren't already available. It then checks
     * if the given module exists, and if not it creates one, generating an ID.
     *
     * @param  String $module
     * @return int
     */
    public static function getModuleId($module)
    {
        if (empty(self::$module_ids)) {
            $result = Database::getInstance()->fetchAll('SELECT name, moduleid FROM myury.modules');
            foreach ($result as $row) {
                self::$module_ids[$row['name']] = $row['moduleid'];
            }
        }

        if (empty(self::$module_ids[$module])) {
            //The module needs creating
            $result = Database::getInstance()->fetchColumn(
                'INSERT INTO myury.modules (serviceid, name)
                VALUES ($1, $2) RETURNING moduleid',
                [Config::$service_id, $module]
            );
            self::$module_ids[$module] = $result[0];
        }

        return self::$module_ids[$module];
    }

    /**
     * Returns the ID of a Service/Module/Action request, creating it if necessary
     * @param  int    $module
     * @param  String $action
     * @return int
     */
    public static function getActionId($module, $action)
    {
        if (empty(self::$action_ids)) {
            $result = Database::getInstance()->fetchAll('SELECT name, moduleid, actionid FROM myury.actions');
            foreach ($result as $row) {
                self::$action_ids[$row['name'] . '-' . $row['moduleid']] = $row['actionid'];
            }
        }

        if (empty(self::$action_ids[$action . '-' . $module])) {
            //The action needs creating
            $result = Database::getInstance()->fetchColumn(
                'INSERT INTO myury.actions (moduleid, name)
                VALUES ($1, $2) RETURNING actionid',
                [$module, $action]
            );
            self::$action_ids[$action . '-' . $module] = $result[0];
        }

        return self::$action_ids[$action . '-' . $module];
    }

    /**
     * Assigns a permission to a command
     * @todo Document
     * @param type $module
     * @param type $action
     * @param type $permission
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
     * Returns the service version allocated to the given user.
     *
     * @param MyRadio_User $user If given this is the user to check. By default,
     *                           it uses the currently logged-in user.
     *
     * If there is no user logged in, then the default version is returned.
     */
    public static function getServiceVersionForUser(MyRadio_User $user = null)
    {
        if ($user === null) {
            if (!isset($_SESSION['memberid'])) {
                return self::getDefaultServiceVersion();
            }
            $user = MyRadio_User::getInstance();
        }
        $serviceid = Config::$service_id;
        $key = $serviceid . '-' . $user->getID();

        if ($user->getID() === MyRadio_User::getInstance()->getID()) {
            //It's the current user. If they have an override defined in their session, use that.
            if (isset($_SESSION['myury_svc_version_' . $serviceid])) {
                return [
                    'version' => $_SESSION['myury_svc_version_' . $serviceid],
                    'path' => $_SESSION['myury_svc_version_' . $serviceid . '_path'],
                    'proxy_static' => $_SESSION['myury_svc_version_' . $serviceid . '_proxy_static']
                ];
            }
        }

        if (!isset(self::$svc_version_cache[$key])) {
            $db = Database::getInstance();

            $result = $db->fetchOne(
                'SELECT version, path, proxy_static
                FROM myury.services_versions
                WHERE serviceid IN (
                    SELECT serviceid FROM myury.services_versions_member
                    WHERE memberid=$2 AND serviceversionid IN (
                        SELECT serviceversionid FROM myury.services_versions
                        WHERE serviceid=$1
                    )
                )',
                [$serviceid, $user->getID()]
            );

            if (empty($result)) {
                self::$svc_version_cache[$key] = self::getDefaultServiceVersion();
            } else {
                $result['proxy_static'] = $result['proxy_static'] === 't';
                self::$svc_version_cache[$key] = $result;
            }
        }

        //If it's the current user, store the data in session.
        if ($user->getID() === MyRadio_User::getInstance()->getID()) {
            $_SESSION['myury_svc_version_' . $serviceid] = self::$svc_version_cache[$key]['version'];
            $_SESSION['myury_svc_version_' . $serviceid . '_path'] = self::$svc_version_cache[$key]['path'];
            $_SESSION['myury_svc_version_' . $serviceid . '_proxy_static'] = self::$svc_version_cache[$key]['proxy_static'];
        }

        return self::$svc_version_cache[$key];
    }

    /**
     *
     */
    private static function getDefaultServiceVersion()
    {
        $db = Database::getInstance();

        $r = $db->fetchOne(
            'SELECT version, path, proxy_static FROM myury.services_versions WHERE serviceid=$1
            AND is_default=true LIMIT 1',
            [Config::$service_id]
        );
        $r['proxy_static'] = $r['proxy_static'] === 't';

        return $r;
    }

    /**
     * @todo Document this.
     * @return boolean
     */
    public static function getServiceVersions()
    {
        $db = Database::getInstance();

        return $db->fetchAll('SELECT version, path, proxy_static FROM myury.services_versions WHERE serviceid=$1', [Config::$service_id]);
    }

    /**
     * Parses an object or array into client array datasource
     * @param  mixed $data
     * @return array
     */
    public static function dataSourceParser($data, $full = true)
    {
        if (is_object($data) && $data instanceof MyRadio_DataSource) {
            return $data->toDataSource($full);
        } elseif (is_array($data)) {
            foreach ($data as $k => $v) {
                $data[$k] = self::dataSourceParser($v, $full);
            }

            return $data;
        } else {
            return $data;
        }
    }

    //from http://www.php.net/manual/en/function.xml-parse-into-struct.php#109032
    public static function xml2array($xml)
    {
        $opened = [];
        $opened[1] = 0;
        $xml_parser = xml_parser_create();
        xml_parse_into_struct($xml_parser, $xml, $xmlarray);
        $array = array_shift($xmlarray);
        unset($array["level"]);
        unset($array["type"]);
        $arrsize = sizeof($xmlarray);
        for ($j = 0; $j < $arrsize; $j++) {
            $val = $xmlarray[$j];
            switch ($val["type"]) {
                case "open":
                    $opened[$val["level"]] = 0;
                    /* Fall through */
                case "complete":
                    $index = "";
                    for ($i = 1; $i < ($val["level"]); $i++) {
                        $index .= "[" . $opened[$i] . "]";
                    }
                    $path = explode('][', substr($index, 1, -1));
                    $value = &$array;
                    foreach ($path as $segment) {
                        $value = &$value[$segment];
                    }
                    $value = $val;
                    unset($value["level"]);
                    unset($value["type"]);
                    if ($val["type"] == "complete") {
                        $opened[$val["level"] - 1] ++;
                    }
                    break;
                case "close":
                    $opened[$val["level"] - 1] ++;
                    unset($opened[$val["level"]]);
                    break;
            }
        }

        return $array;
    }

    public static function requireTimeslot()
    {
        if (!isset($_SESSION['timeslotid'])) {
            self::redirect('MyRadio', 'timeslot', ['next' => $_SERVER['REQUEST_URI']]);
            exit;
        }
    }

    public static function backWithMessage($message)
    {
        header('Location: ' . $_SERVER['HTTP_REFERER'] . (strstr($_SERVER['HTTP_REFERER'], '?') !== false ? '&' : '?') . 'message=' . base64_encode($message));
    }

    /**
     * Returns a randomly selected item from the list, in a biased manner
     * Weighted should be an integer - how many times to put the item into the bag
     * @param Array $data 2D of Format [['item' => mixed, 'weight' => n], ...]
     */
    public static function biasedRandom($data)
    {
        $bag = [];

        foreach ($data as $ball) {
            for (; $ball['weight'] > 0; $ball['weight'] --) {
                $bag[] = $ball['item'];
            }
        }

        return $bag[array_rand($bag)];
    }

    //Reports some things
    public static function shutdown()
    {
        session_write_close(); //It doesn't seem to do this itself sometimes.
        try {
            $db = Database::getInstance();
        } catch (MyRadioException $e) {
            return;
        }
        if (!empty($_SERVER['SERVER_ADDR'])) {
            //Don't let the client wait for us
            flush();
        }

        $errors = MyRadioError::getErrorCount();
        $exceptions = MyRadioException::getExceptionCount();
        $queries = $db->getCounter();
        $host = gethostbyname(gethostname());

        $db->query(
            'INSERT INTO myury.error_rate (server_ip, error_count, exception_count, queries)
            VALUES ($1, $2, $3, $4)',
            [$host, $errors, $exceptions, $queries]
        );
    }

    /**
     * Ring YUSU's API and ask how it's doing
     *
     * Currently, ListMembers is the only function available. Dan Bishop has plans for more at a later date.
     *
     * @return Array JSON Response, forced to assoc array
     */
    public static function callYUSU($function = 'ListMembers')
    {
        $options = [
            'http' => [
                'method' => "GET",
                'header' => "User-Agent: MyRadio\r\n"
            ]
        ];
        $context = stream_context_create($options);

        return json_decode(
            file_get_contents(
                'https://www.yusu.org/api/api.php?apikey='
                . Config::$yusu_api_key
                . '&function='
                . $function,
                false,
                $context
            ),
            true
        );
    }

    public static function getErrorStats($since = null)
    {
        if ($since === null) {
            $since = time() - 86400;
        }
        $result = Database::getInstance()->fetchAll(
            'SELECT
            round(extract(\'epoch\' from timestamp) / 600) * 600 as timestamp,
            SUM(error_count)/COUNT(error_count) AS errors, SUM(exception_count)/COUNT(exception_count) AS exceptions,
            SUM(queries)/COUNT(queries) AS queries
            FROM myury.error_rate WHERE timestamp>=$1 GROUP BY round(extract(\'epoch\' from timestamp) / 600)
            ORDER BY timestamp ASC',
            [self::getTimestamp($since)]
        );

        $return = [];
        $return[] = ['Timestamp', 'Errors per request', 'Exceptions per request', 'Queries per request'];
        foreach ($result as $row) {
            $return[] = [date('H:i', $row['timestamp']), (int) $row['errors'], (int) $row['exceptions'], (int) $row['queries']];
        }

        return $return;
    }

    public static function getSafeHTML($dirty_html)
    {
        require_once 'Classes/vendor/htmlpurifier/HTMLPurifier.auto.php';
        $config = \HTMLPurifier_Config::createDefault();
        $purifier = new \HTMLPurifier($config);

        return $purifier->purify($dirty_html);
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
     * @param  String             $user
     * @param  String             $pass
     * @return MyRadio_User|false
     * @api POST
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
                if (Config::$single_authenticator &&
                        $result->getAuthProvider() !== null &&
                        $result->getAuthProvider() !== $authenticator) {
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
     * Returns information about the $_REQUEST array.
     *
     * This *MUST* be used instead of print_r($_REQUEST) or var_dump($_REQUEST)
     * in debug output.
     *
     * @return String var_dump output
     */
    public static function getRequestInfo()
    {
        ob_start();
        if (isset($_REQUEST['redact'])) {
            $info = [];
            foreach ($_REQUEST as $k => $v) {
                if (!in_array($k, $_REQUEST['redact'])) {
                    $info[$k] = $v;
                } else {
                    $info[$k] = '**REDACTED**';
                }
            }
            var_dump($info);
        } else {
            var_dump($_REQUEST);
        }
        return ob_get_clean();
    }

    /**
     * Generates a completely pseudorandom string, aimed for Salt purposes.
     * @param int $pwdLen The length of the string to generate
     * @return String a random string of length $pwdLen
     */
    public static function randomString($pwdLen = 8)
    {
        $result = '';
        $pwdSource = '1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        srand((double) microtime() * 1000000);
        while ($pwdLen) {
            $result .= substr($pwdSource, rand(0, strlen($pwdSource) - 1), 1);
            $pwdLen--;
        }
        return( $result );
    }

    private function __construct()
    {
    }

    /**
     * Generates a new password consisting of two words and a two-digit number
     * @todo Make this crypto secure random?
     * @return String
     */
    public static function newPassword()
    {
        return self::$words[array_rand(self::$words)] . rand(10, 99)
            . self::$words[array_rand(self::$words)];
    }

    /**
     * Words used by CoreUtils::newPassword
     * @var String[]
     */
    private static $words = [
        'Radio',
        'Microphone',
        'Studio',
        'Speaker',
        'Headphone',
        'Compressor',
        'Fader',
        'Schedule',
        'Podcast',
        'Music',
        'Track',
        'Record',
        'Artist',
        'Publisher',
        'Album',
        'Broadcast',
        'Transmitter',
        'Silence',
        'Selector',
        'Management',
        'Engineering',
        'Computing',
        'Business',
        'Events',
        'Speech',
        'Training',
        'Presenting',
        'Stores',
        'Tardis',
        'Relay',
        'Jingle',
        'Advert',
        'Frequency',
        'Modulation',
        'Vinyl',
        'Broadcasting'
    ];
}
