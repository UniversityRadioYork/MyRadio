<?php

/**
 * Standard API Utilities. Basically miscellaneous function
 *
 * @author lpw
 */
class CoreUtils {

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

}