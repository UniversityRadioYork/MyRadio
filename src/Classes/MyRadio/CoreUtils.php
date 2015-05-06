<?php

/**
 * This file provides the CoreUtils class for MyRadio
 * @package MyRadio_Core
 */

namespace MyRadio\MyRadio;

use \MyRadio\MyRadioTwig;
use \MyRadio\MyRadioException;
use \MyRadio\MyRadioError;
use \MyRadio\ServiceAPI\MyRadio_User;
use \MyRadio\Iface\MyRadio_DataSource;

use \MyRadio\Traits\Configurable;
use \MyRadio\Traits\DatabaseSubject;
use \MyRadio\Traits\ServiceFactorySubject;
use \MyRadio\Traits\SessionSubject;

/**
 * Standard API Utilities. Basically miscellaneous functions.
 *
 * @package MyRadio_Core
 */
class CoreUtils
{
    use Configurable;
    use DatabaseSubject;
    use ServiceFactorySubject;
    use SessionSubject;

    /**
     * This stores whether the Permissions have been defined to prevent re-defining, causing errors and wasting time
     * Once setUpAuth is run, this is set to true to prevent subsequent runs
     * @var boolean
     */
    private $auth_cached = false;

    /**
     * Stores the result of CoreUtils::getAcademicYear
     *
     * @var int
     */
    private $academicYear;

    /**
     * Stores permission typeid => description mappings
     * @var Array
     */
    private $typeid_descr = [];

    /**
     * Stores module name => id mappings to reduce query load - they are initialised once and stored
     * @var Array
     */
    private $module_ids = [];

    /**
     * Stores action name => id mappings to reduce query load - they are initialised once and stored
     * @var Array
     */
    private $action_ids = [];

    /**
     * Checks whether a given Module/Action combination is valid
     * @param  String $module The module to check
     * @param  String $action The action to check. Default 'default'
     * @return boolean Whether or not the request is valid
     * @assert ('MyRadio', 'default') === true
     * @assert ('foo', 'barthatdoesnotandwillnoteverexisteverbecauseitwouldbesilly') === false
     * @assert ('../foo', 'bar') === false
     * @assert ('foo', '../bar') === false
     */
    public function isValidController($module, $action = null)
    {
        if ($action === null) {
            $action = $this->config->default_action;
        }
        try {
            $this->actionSafe($action);
            $this->actionSafe($module);
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
     * @todo Deprecate this, add autoloader to init, and replace with Container pattern
     * @assert () !== false
     * @assert () !== null
     */
    public function getTemplateObject()
    {
        require_once 'vendor/twig/twig/lib/Twig/Autoloader.php';
        \Twig_Autoloader::register();

        return new MyRadioTwig();
    }

    /**
     * Checks whether a requested action is safe
     * @param  String $action A module action
     * @return boolean          Whether the module is safe to be used on a filesystem
     * @throws MyRadioException Thrown if directory traversal detected
     * @assert ('safe!') === true
     * @assert ('../notsafe!') throws MyRadioException
     */
    public function actionSafe($action)
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
     * @assert (40000, false) == '01/01/1970'
     */
    public function happyTime($timestring, $time = true, $date = true)
    {
        return date(
            ($date ? 'd/m/Y' : '') .
            ($time && $date ? ' ' : '') .
            ($time ? 'H:i' : ''),
            is_numeric($timestring) ? $timestring : strtotime($timestring)
        );
    }

    /**
     * Formats a number into h:m:s format.
     * @param  int $int
     * @return String
     */
    public function intToTime($int)
    {
        $time = date('i:s', $int);
        if ($int >= 3600) {
            $time = str_pad(floor($int / 3600), 2, '0', STR_PAD_LEFT) . ':' . $time;
        }

        return $time;
    }

    /**
     * Returns a postgresql-formatted timestamp
     * @param  int $time The time to get the timestamp for. Default right now.
     * @return String a timestamp
     * @assert (30) == '1970-01-01 00:00:30+00'
     */
    public function getTimestamp($time = null)
    {
        if ($time === null) {
            $time = time();
        }

        return gmdate('Y-m-d H:i:s+00', $time);
    }

    /**
     * Returns the ISO8601 Year and Week Number for the given time
     * @param int $time The time to get the info for, default now.
     * @return array [year, week_number]
     */
    public function getYearAndWeekNo($time = null)
    {
        if ($time === null) {
            $time = time();
        }

        $year_absolute = (int)gmdate('Y', $time);
        $week_number = (int)gmdate('W', $time);
        $month = (int)gmdate('n', $time);

        if ($month === 1 && $week_number > 50) {
            //This is the final week of *last* year
            $year_adjusted = $year_absolute - 1;
        } else {
            $year_adjusted = $year_absolute;
        }

        return [$year_adjusted, $week_number];
    }

    /**
     * Gives you the starting year of the current academic year
     * @return int year
     * @assert () == 2013
     */
    public function getAcademicYear()
    {
        if (empty($this->academicYear)) {
            $term = $this->db->fetchColumn(
                'SELECT start FROM public.terms WHERE descr=\'Autumn\'
                AND EXTRACT(year FROM start) = $1',
                [date('Y')]
            );

            // Default to this year
            $account_reset_time = strtotime('+' . $this->config->account_expiry_before . ' days');
            if (empty($term) || strtotime($term[0]) <= $account_reset_time) {
                $this->academicYear = date('Y');
            } else {
                $this->academicYear = date('Y') - 1;
            }
        }

        return $this->academicYear;
    }

    /**
     * Returns a postgresql formatted interval
     * @param  int $start The start time
     * @param  int $end   The end time
     * @return String a PgSQL valid interval value
     * @assert (0, 0) == '0 seconds'
     */
    public function makeInterval($start, $end)
    {
        return $end - $start . ' seconds';
    }

    /**
     * Redirects back to previous page.
     * @codeCoverageIgnore
     */
    public function back()
    {
        header('Location: ' . $_SERVER['HTTP_REFERER']);
    }

    /**
     * Responds with nocontent.
     * @codeCoverageIgnore
     */
    public function nocontent()
    {
        header('HTTP/1.1 204 No Content');
    }

    /**
     * Responds with JSON data.
     */
    public function dataToJSON($data)
    {
        header('Content-Type: application/json');
        header('HTTP/1.1 200 OK');

        //Decode to datasource if needed
        $data = $this->dataSourceParser($data);

        $canDisplayErr = $this->config->display_errors || CoreUtils::hasPermission(AUTH_SHOWERRORS);
        if (!empty(MyRadioError::$php_errorlist) && $canDisplayErr) {
            $data['myradio_errors'] = MyRadioError::$php_errorlist;
        }

        return json_encode($data, JSON_NUMERIC_CHECK | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Redirects to another page.
     *
     * @param  string $module The module to which we should redirect.
     * @param  string $action The optional action inside the module to target.
     * @param  array  $params Additional GET variables
     * @return null   Nothing.
     * @codeCoverageIgnore
     */
    public function redirect($module, $action = null, $params = [])
    {
        header('Location: ' . $this->makeURL($module, $action, $params));
    }

    /**
     * @codeCoverageIgnore
     */
    public function redirectWithMessage($module, $action, $message)
    {
        $this->redirect($module, $action, ['message' => base64_encode($message)]);
    }

    /**
     * Builds a module/action URL
     * @param  string $module
     * @param  string $action
     * @param  array  $params Additional GET variables
     * @return String URL to Module/Action
     * @todo remove deprecated custom_uri functionality?
     */
    public function makeURL($module, $action = null, $params = [])
    {
        if ($this->config->rewrite_url) {
            $str = $this->config->base_url . $module . '/' . (($action !== null) ? $action . '/' : '');
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
            $str = $this->config->base_url . '?module=' . $module . (($action !== null) ? '&action=' . $action : '');

            if (!empty($params)) {
                if (is_string($params)) {
                    if (substr($params, 0, 1) === '?') {
                        $params = substr($params, 1);
                    }
                    $str .= "&$params";
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
    public function setUpAuth()
    {
        if ($this->auth_cached) {
            return;
        }

        $result = $this->db->fetchAll('SELECT typeid, phpconstant, descr FROM l_action');
        foreach ($result as $row) {
            define($row['phpconstant'], $row['typeid']);
            $this->typeid_descr[$row['typeid']] = $row['descr'];
        }

        $this->auth_cached = true;
    }

    /**
     * Returns the Actions and API Endpoints that utilise a given type.
     *
     * @param  int $typeid
     * @return [[action,...], [api method,...]]
     */
    public function getAuthUsage($typeid)
    {
        $db = $this->db;
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
     * @param  int $typeid
     * @return String
     */
    public function getAuthDescription($typeid)
    {
        $this->setUpAuth();

        return $this->$typeid_descr[$typeid];
    }

    /**
     * Checks using cached permissions whether the current member has the specified permission
     * @param  int $permission The ID of the permission, resolved by using an AUTH_ constant
     * @return boolean Whether the member has the requested permission
     */
    public function hasPermission($permission)
    {
        try {
            return $this->factory->getInstanceOf('MyRadio_User')->hasAuth($permission);
        } catch (MyRadioException $e) {
            return $permission === null;
        }
    }

    /**
     * Checks if the user has the given permission. Or, alternatiely, if we are currently running CLI, returns true.
     * @param  int $permission A permission constant to check
     * @return void Will Fatal error if the user does not have the permission
     */
    public function requirePermission($permission)
    {
        if (php_sapi_name() === 'cli') {
            return true; //Non-interactive version has God Rights.
        }
        if (!$this->hasPermission($permission)) {
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
    public function requirePermissionAuto($module, $action)
    {
        $this->setUpAuth();
        $db = $this->db;

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
            throw new MyRadioException('There are no permissions defined for the ' . $module . '/' . $action . ' action!', 500);
        }

        $authorised = false;
        foreach ($result as $permission) {
            //It only needs to match one
            if ($permission === AUTH_NOLOGIN || ($this->hasPermission($permission) && $this->session['auth_use_locked'] === false)) {
                $authorised = true;
                break;
            }
        }

        if (!$authorised) {
            //Requires login
            if (!isset($this->session['memberid']) || $this->session['auth_use_locked'] !== false) {
                throw new MyRadioException('Login required', 401);
            }
        }

        //Return whether authorised
        return $authorised;
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
     */
    public function getAllActionPermissions()
    {
        return $this->db->fetchAll(
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
    public function getAllPermissions()
    {
        return $this->db->fetchAll(
            'SELECT typeid AS value, descr AS text FROM public.l_action
            ORDER BY descr ASC'
        );
    }

    /**
     * udiff function for permission value equality
     * @param  array $perm1 permission value & description
     * @param  array $perm2 permission value & description
     * @return int          comparison result
     */
    private function comparePermission($perm1, $perm2)
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
     * Returns all permissions that are in $perms but not $diffPerms
     * @param   $perms array of permissions
     * @param   $diffPerms array of permissions
     * @return array        all permissions not included in $perms
     */
    public function diffPermissions($perms, $diffPerms)
    {
        return array_udiff($perms, $diffPerms, '$this->comparePermission');
    }

    /**
     * Add a new permission constant to the database.
     * @param String $descr    A useful friendly description of what this action means.
     * @param String $constant /AUTH_[A-Z_]+/
     */
    public function addPermission($descr, $constant)
    {
        $value = (int)$this->db->fetchColumn(
            'INSERT INTO public.l_action (descr, phpconstant)
            VALUES ($1, $2) RETURNING typeid',
            [$descr, $constant]
        )[0];
        define($constant, $value);
        return $value;
    }

    /**
     * A simple debug method that only displays output for a specific user.
     * @param int    $userid  The ID of the user to display for
     * @param String $message The HTML to display for this user
     * @assert (7449, 'Test') == null
     */
    public function debugFor($userid, $message)
    {
        if ($this->$container['session']['memberid'] == $userid) {
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
    public function getModuleId($module)
    {
        if (empty($this->$module_ids)) {
            $result = $this->db->fetchAll('SELECT name, moduleid FROM myury.modules');
            foreach ($result as $row) {
                $this->$module_ids[$row['name']] = $row['moduleid'];
            }
        }

        if (empty($this->$module_ids[$module])) {
            //The module needs creating
            $result = $this->db->fetchColumn(
                'INSERT INTO myury.modules (serviceid, name)
                VALUES ($1, $2) RETURNING moduleid',
                [$this->config->service_id, $module]
            );
            if ($result) {
                $this->$module_ids[$module] = $result[0];
            } else {
                return null;
            }
        }

        return $this->$module_ids[$module];
    }

    /**
     * Returns the ID of a Service/Module/Action request, creating it if necessary
     * @param  int    $module
     * @param  String $action
     * @return int
     */
    public function getActionId($module, $action)
    {
        if (empty($this->$action_ids)) {
            $result = $this->db->fetchAll('SELECT name, moduleid, actionid FROM myury.actions');
            foreach ($result as $row) {
                $this->$action_ids[$row['name'] . '-' . $row['moduleid']] = $row['actionid'];
            }
        }

        if (empty($this->$action_ids[$action . '-' . $module])) {
            //The action needs creating
            $result = $this->db->fetchColumn(
                'INSERT INTO myury.actions (moduleid, name)
                VALUES ($1, $2) RETURNING actionid',
                [$module, $action]
            );
            if ($result) {
                $this->$action_ids[$action . '-' . $module] = $result[0];
            } else {
                return null;
            }
        }

        return $this->$action_ids[$action . '-' . $module];

    }

    /**
     * Assigns a permission to a command. Note arguments are the integer IDs
     * NOT the String names
     *
     * @param int $module     The module ID
     * @param int $action     The action ID
     * @param int $permission The permission typeid
     */
    public function addActionPermission($module, $action, $permission)
    {
        $db = $this->db;
        $db->query(
            'INSERT INTO myury.act_permission (serviceid, moduleid, actionid, typeid)
            VALUES ($1, $2, $3, $4)',
            [$this->config->service_id, $module, $action, $permission]
        );
    }

    /**
     * Parses an object or array into client array datasource
     * @param  mixed $data
     * @return array
     */
    public function dataSourceParser($data, $full = true)
    {
        if (is_object($data) && $data instanceof MyRadio_DataSource) {
            return $data->toDataSource($full);
        } elseif (is_array($data)) {
            foreach ($data as $k => $v) {
                $data[$k] = $this->dataSourceParser($v, $full);
            }

            return $data;
        } else {
            return $data;
        }
    }

    //from http://www.php.net/manual/en/function.xml-parse-into-struct.php#109032
    public function xml2array($xml)
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

    public function requireTimeslot()
    {
        if (!isset($this->$container['session']['timeslotid'])) {
            $this->redirect('MyRadio', 'timeslot', ['next' => $_SERVER['REQUEST_URI']]);
            exit;
        }
    }

    public function backWithMessage($message)
    {
        header('Location: ' . $_SERVER['HTTP_REFERER'] . (strstr($_SERVER['HTTP_REFERER'], '?') !== false ? '&' : '?') . 'message=' . base64_encode($message));
    }

    /**
     * Returns a randomly selected item from the list, in a biased manner
     * Weighted should be an integer - how many times to put the item into the bag
     * @param Array $data 2D of Format [['item' => mixed, 'weight' => n], ...]
     */
    public function biasedRandom($data)
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
    public function shutdown()
    {
        session_write_close(); //It doesn't seem to do this itself sometimes.
        try {
            $db = $this->db;
        } catch (MyRadioException $e) {
            return;
        }
        if (!empty($_SERVER['SERVER_ADDR'])) {
            //Don't let the client wait for us
            flush();
        }

        //Discard any in-progress transactions
        if ($db->getInTransaction()) {
            $db->query('ROLLBACK');
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
    public function callYUSU($function = 'ListMembers')
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
                . $this->config->yusu_api_key
                . '&function='
                . $function,
                false,
                $context
            ),
            true
        );
    }

    public function getErrorStats($since = null)
    {
        if ($since === null) {
            $since = time() - 86400;
        }
        $result = $this->db->fetchAll(
            'SELECT
            round(extract(\'epoch\' from timestamp) / 600) * 600 as timestamp,
            SUM(error_count)/COUNT(error_count) AS errors, SUM(exception_count)/COUNT(exception_count) AS exceptions,
            SUM(queries)/COUNT(queries) AS queries
            FROM myury.error_rate WHERE timestamp>=$1 GROUP BY round(extract(\'epoch\' from timestamp) / 600)
            ORDER BY timestamp ASC',
            [$this->getTimestamp($since)]
        );

        $return = [];
        $return[] = ['Timestamp', 'Errors per request', 'Exceptions per request', 'Queries per request'];
        foreach ($result as $row) {
            $return[] = [date('H:i', $row['timestamp']), (int) $row['errors'], (int) $row['exceptions'], (int) $row['queries']];
        }

        return $return;
    }

    public function getSafeHTML($dirty_html)
    {
        $config = \HTMLPurifier_Config::createDefault();
        $purifier = new \HTMLPurifier($config);

        return $purifier->purify($dirty_html);
    }

    /**
     * Returns lookup values for Status for a select box
     * @return array
     */
    public function getStatusLookup()
    {
        return $this->db->fetchAll(
            'SELECT statusid AS value, descr AS text FROM public.l_status
            ORDER BY descr ASC'
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
     * @param  String $user
     * @param  String $pass
     * @return MyRadio_User|false
     * @api    POST
     */
    public function testCredentials($user, $pass)
    {
        //Make a best guess at the user account
        //This way we can skip authenticators if they have one set
        $u = MyRadio_User::findByEmail($user);
        if ($u instanceof MyRadio_User && $u->getAuthProvider() !== null) {
            $authenticators = [$u->getAuthProvider()];
        } else {
            $authenticators = $this->config->authenticators;
        }

        //Iterate over each authenticator
        foreach ($authenticators as $authenticator) {
            $a = new $authenticator();
            $result = $a->validateCredentials($user, $pass);
            if ($result instanceof MyRadio_User) {
                if ($this->config->single_authenticator
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
     * Returns information about the $_REQUEST array.
     *
     * This *MUST* be used instead of print_r($_REQUEST) or var_dump($_REQUEST)
     * in debug output.
     *
     * @return String var_dump output
     */
    public function getRequestInfo()
    {
        ob_start();
        if (isset($_REQUEST['redact']) || isset($_REQUEST['pass']) || isset($_REQUEST['password'])) {
            $info = [];
            foreach ($_REQUEST as $k => $v) {
                if (!in_array($k, $_REQUEST['redact']) && $k !== 'pass' && $k !== 'password') {
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
     * Returns if the current request is via ajax
     */
    public function isAjax()
    {
        return (
                isset($_SERVER['HTTP_X_REQUESTED_WITH'])
                && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
            )
            || empty($_SERVER['REMOTE_ADDR']
        );
    }

    /**
     * Generates a completely pseudorandom string, aimed for Salt purposes.
     * @param int $pwdLen The length of the string to generate
     * @return String a random string of length $pwdLen
     */
    public function randomString($pwdLen = 8)
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

    /**
     * Generates a new password consisting of two words and a two-digit number
     * @todo Make this crypto secure random?
     * @return String
     */
    public function newPassword()
    {
        return $this->$words[array_rand($this->$words)] . rand(10, 99)
            . $this->$words[array_rand($this->$words)];
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
