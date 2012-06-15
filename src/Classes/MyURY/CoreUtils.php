<?php

/**
 * Standard API Utilities. Basically miscellaneous functions for the core system
 * No database accessing etc should be setup here.
 *
 * @author lpw
 */
class CoreUtils {
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
   * Builds a module/action URL
   * @todo Finish and document.
   * @param type $module
   * @param type $action
   * @return type
   * @throws MyURYException 
   */
  public static function makeURL($module, $action) {
    if (Config::$rewrite_url) throw new MyURYException('Rewritten URLs not implemented');
    return Config::$base_url . '?module=' . $module . '&action=' . $action;
  }
  
  /**
   * Sets up the Authentication Constants
   * @return void 
   */
  public static function setUpAuth() {
    if (isset(self::$auth_cached)) return;
    
    $db = Database::getInstance();
    $result = $db->fetch_all('SELECT typeid, phpconstant FROM l_action');
    
    foreach ($result as $row) {
      define($row['phpconstant'], $row['typeid']);
    }
    
    self::$auth_cached = true;
  }
  
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
      //Load the 500 controller and exit
      require 'Controllers/Errors/403.php';
      exit;
    }
  }

}