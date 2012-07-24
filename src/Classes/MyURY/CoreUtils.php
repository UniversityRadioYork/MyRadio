<?php
/**
 * This file provides the CoreUtils class for MyURY
 * @package MyURY_Core
 */


/**
 * Standard API Utilities. Basically miscellaneous functions for the core system
 * No database accessing etc should be setup here.
 *
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 21072012
 * @package MyURY_Core
 */
class CoreUtils {
  /**
   * This stores whether the Permissions have been defined to prevent re-defining, causing errors and wasting time
   * Once setUpAuth is run, this is set to true to prevent subsequent runs
   * @var boolean
   */
  private static $auth_cached = false;

  /**
   * Checks whether a given Module/Action combination is valid
   * @param String $module The module to check
   * @param String $action The action to check. Default 'default'
   * @return boolean Whether or not the request is valid
   */
  public static function isValidController($module, $action = 'default') {
    try {
      self::actionSafe($action);
      self::actionSafe($module);
    } catch (MyURYException $e) {
      return false;
    }
    return file_exists(__DIR__ . '/../../Controllers/MyURY/' . $module . '/' . $action . '.php');
  }

  /**
   * Provides a template engine object compliant with TemplateEngine interface
   * @return URYTwig 
   * @todo Make this generalisable for drop-in template engine replacements
   */
  public static function getTemplateObject() {
    require_once 'Twig/Autoloader.php';
    Twig_Autoloader::register();
    require_once 'Classes/URYTwig.php';
    return new URYTwig();
  }

  /**
   * Checks whether a requested action is safe
   * @param String $action A module action
   * @return boolean Whether the module is safe to be used on a filesystem
   * @throws MyURYException Thrown if directory traversal detected
   */
  public static function actionSafe($action) {
    if (strpos($action, '/') !== false) {
      //Someone is trying to traverse directories
      throw new MyURYException('Directory Traversal Thrwated');
      return false;
    }
    return true;
  }
  
  /**
   * Formats pretty much anything into a happy, human readable date/time
   * @param string $timestring Some form of time
   * @param bool $time Whether to include Hours,Mins,Secs. Default yes
   * @return String A happy time 
   */
  public static function happyTime($timestring, $time = true) {
    return date('d/m/Y' . ($time ? ' H:i:s' : ''), strtotime($timestring));
  }
  
  /**
   * Gives you the starting year of the current academic year
   * @return int year
   */
  public static function getAcademicYear() {
    if (date('m') >= 10) return (int)date('Y');
    else return (int)date('Y')-1;
  }

  /**
   * Builds a module/action URL
   * @todo Finish and document.
   * @param type $module
   * @param type $action
   * @param type $params
   * @return type
   * @throws MyURYException 
   */
  public static function makeURL($module, $action, $params = array()) {
    if (Config::$rewrite_url) throw new MyURYException('Rewritten URLs not implemented');
    $str = Config::$base_url . '?module=' . $module . '&action=' . $action;
    
    foreach ($params as $k => $v) {
      $str .= "&$k=$v";
    }
    return $str;
  }
  
  /**
   * Sets up the Authentication Constants
   * @return void 
   */
  public static function setUpAuth() {
    if (self::$auth_cached) return;
    
    $db = Database::getInstance();
    $result = $db->fetch_all('SELECT typeid, phpconstant FROM l_action');
    foreach ($result as $row) {
      define($row['phpconstant'], $row['typeid']);
    }
    
    self::$auth_cached = true;
  }
  
  /**
   * Checks using cached Shibbobleh permissions whether the current member has the specified permission
   * @param int $permission The ID of the permission, resolved by using an AUTH_ constant
   * @return boolean Whether the member has the requested permission
   */
  public static function hasPermission($permission) {
    if (!isset($_SESSION['member_permissions'])) return false;
    return in_array($permission, $_SESSION['member_permissions']);
  }
  
  /**
   * Checks if the user has the given permission
   * @param int $permission A permission constant to check
   * @return void Will Fatal error if the user does not have the permission
   */
  public static function requirePermission($permission) {
    if (!self::hasPermission($permission)) {
      //Load the 403 controller and exit
      require 'Controllers/Errors/403.php';
      exit;
    }
  }
  
  /**
   * Checks if the user has the given permissions required for the given Service/Module/Action combination
   * 
   * The query needs a little bit of explaining.<br>
   * The first three WHERE clauses just set up foreign key references - we're searching by name, not ID.<br>
   * The next three WHERE clauses return exact or wildcard matches for this Service/Module/Action combination.<br>
   * The final two AND NOT phrases make sure it ignores wildcards that allow any access.
   * 
   * @param String $service The Service to check permissions for
   * @param String $module The Module to check permissions for
   * @param String $action The Action to check permissions for
   * @param bool $require If true, will die if the user does not have permission. If false, will just return false
   * @return bool True on required or authorised, false on unauthorised
   */
  public static function requirePermissionAuto($service, $module, $action, $require = true) {
    self::setUpAuth();
    $db = Database::getInstance();
    /**
     * 
     */
    $result = $db->fetch_column('SELECT typeid FROM myury.act_permission, myury.services, myury.modules, myury.actions
      WHERE myury.act_permission.actionid=myury.actions.actionid
      AND myury.act_permission.moduleid=myury.modules.moduleid
      AND myury.act_permission.serviceid=myury.services.serviceid
      AND myury.services.name=$1
      AND (myury.modules.name=$2 OR myury.act_permission.moduleid IS NULL)
      AND (myury.actions.name=$3 OR myury.act_permission.actionid IS NULL)
      AND NOT (myury.act_permission.actionid IS NULL AND myury.act_permission.typeid IS NULL)
      AND NOT (myury.act_permission.moduleid IS NULL AND myury.act_permission.typeid IS NULL)',
    array($service, $module, $action));
    
    //Don't allow empty result sets - throw an Exception as this is very very bad.
    if (empty($result)) {
      throw new MyURYException('There are no permissions defined for the '.$service.'/'.$module.'/'.$action.' action!');
      return false;
    }
    
    $authorised = false;
    foreach ($result as $permission) {
      //It only needs to match one
      if ($permission === null || self::hasPermission($permission)) $authorised = true;
    }
    
    if (!$authorised && $require) {
      //Fatal error
      require 'Controllers/Errors/403.php';
      exit;
    }
    
    //Return true on required success, or whether authorised otherwise
    return $require || $authorised;
    
  }
  
  /**
   * Returns a list of all currently defined permissions on MyURY Service/Module/Action combinations.
   *
   * This has multiple UNIONS with similar queries so it gracefully deals with NULL values - the joins lose them.
   * 
   * @todo Is there a nicer way of doing this?
   * 
   * @return Array A 2D Array, where each second dimensions is as follows:<br>
   * action: The name of the Action page<br>
   * module: The name of the Module the action is in<br>
   * service: The name of the Service the module is in<br>
   * permission: The name of the permission applied to that Service/Module/Action combination<br>
   * actpermissionid: The unique ID of this Service/Module/Action combination
   * 
   */
  public static function getAllActionPermissions() {
    return Database::getInstance()->fetch_all(
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
        
        ORDER BY service, module');
  }
  
  /**
   * Returns a list of Permissions ready for direct use in a select MyURYFormField
   * @return Array A 2D Array matching the MyURYFormField::TYPE_SELECT specification.
   */
  public static function getAllPermissions() {
    return Database::getInstance()->fetch_all('SELECT typeid AS value, descr AS text FROM public.l_action
      ORDER BY descr ASC');
  }
  
  /**
   * Returns a list of all MyURY managed Services in a 2D Array.
   * @return Array A 2D Array with each second dimension as follows:<br>
   * value: The ID of the Service
   * text: The Text ID of the Service
   * enabeld: Whether the Service is enabled
   */
  public static function getServices() {
    return Database::getInstance()->fetch_all('SELECT serviceid AS value, name AS text, enabled
      FROM myury.services ORDER BY name ASC');
  }
  
  /**
   * A simple debug method that only displays output for a specific user.
   * @param int $userid The ID of the user to display for
   * @param String $message The HTML to display for this user
   */
  public static function debug_for($userid, $message) {
    if ($_SESSION['memberid'] === $userid) echo '<p>'.$message.'</p>';
  }
  
  /**
   * Returns the ID of a Service/Module combination, creating it if necessary
   * @todo Document this
   * @param type $service
   * @param type $module
   * @return type
   */
  public static function getModuleId($service, $module) {
    $db = Database::getInstance();
    $result = $db->fetch_column('SELECT moduleid FROM myury.modules WHERE serviceid=$1 AND name=$2',
            array($service, $module));
    
    if (empty($result)) {
      //The module needs creating
      $result = $db->fetch_column('INSERT INTO myury.modules (serviceid, name) VALUES ($1, $2) RETURNING moduleid',
              array($service, $module));
    }
    return $result[0];
  }
  
  /**
   * Returns the ID of a Service/Module/Action request, creating it if necessary
   * @todo Document this
   * @param type $module
   * @param type $action
   * @return type
   */
  public static function getActionId($module, $action) {
    $db = Database::getInstance();
    $result = $db->fetch_column('SELECT actionid FROM myury.actions WHERE moduleid=$1 AND name=$2',
            array($module, $action));
    
    if (empty($result)) {
      //The module needs creating
      $result = $db->fetch_column('INSERT INTO myury.actions (moduleid, name) VALUES ($1, $2) RETURNING actionid',
              array($module, $action));
    }
    return $result[0];
  }
  
  /**
   * Assigns a permission to a command
   * @todo Document
   * @param type $service
   * @param type $module
   * @param type $action
   * @param type $permission
   */
  public static function addActionPermission($service, $module, $action, $permission) {
    $db = Database::getInstance();
    $db->query('INSERT INTO myury.act_permission (serviceid, moduleid, actionid, typeid)
      VALUES ($1, $2, $3, $4)', array($service, $module, $action, $permission));
  }

}