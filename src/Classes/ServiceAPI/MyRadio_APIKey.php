<?php

/**
 * Provides the MyRadio_APIKey class for MyRadio
 * @package MyRadio_API
 */

/**
 * The APIKey Class provies information and management of API Keys for the MyRadio
 * REST API.
 * 
 * @version 20130802
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @package MyRadio_API
 * @uses \Database
 */
class MyRadio_APIKey extends ServiceAPI {

  /**
   * The API Key
   * @var String
   */
  private $key;

  /**
   * The Permission flags this API key has.
   * @var int[]
   */
  private $permissions;

  /**
   * Construct the API Key Object
   * @param String $key
   */
  protected function __construct($key) {
    $this->key = $key;
    $this->permissions = self::$db->fetch_column('SELECT typeid FROM myury.api_key_auth WHERE key_string=$1', array($key));
  }

  /**
   * Check if this API Key can call the given Method.
   * 
   * @param String $class The class the method belongs to (actual, not API Alias)
   * @param String $method The method being called
   * @return boolean
   */
  public function canCall($class, $method) {
    if (in_array(AUTH_APISUDO, $this->permissions)) {
      return true;
    }

    $result = self::getCallRequirements($class, $method);

    if ($result === null) {
      return false; //No permissions means the method is not accessible
    }

    if (empty($result)) {
      return true; //An empty array means no permissions needed
    }

    foreach ($result as $type) {
      if (in_array($type, $this->permissions)) {
        return true; //The Key has that permission
      }
    }

    return false; //Didn't match anything...
  }

  /**
   * Logs that this API Key has called something. Used for auditing.
   * 
   * @param String $uri
   * @param Array $args
   */
  public function logCall($uri, $args) {
    self::$db->query('INSERT INTO myury.api_key_log (key_string, remote_ip, request_path, request_params)
      VALUES ($1, $2, $3, $4)', array($this->key, $_SERVER['REMOTE_ADDR'], $uri, json_encode($args)));
  }

  /**
   * Get the permissions that are needed to access this API Call.
   * 
   * If the return values is null, this method cannot be called.
   * If the return value is an empty array, no permissions are needed.
   * 
   * @param String $class The class the method belongs to (actual, not API Alias)
   * @param String $method The method being called
   * @return int[]
   */
  public static function getCallRequirements($class, $method) {
    $result = self::$db->fetch_column('SELECT typeid FROM myury.api_method_auth WHERE class_name=$1 AND 
      (method_name=$2 OR method_name IS NULL)', array($class, $method));

    if (empty($result)) {
      return null;
    }

    foreach ($result as $row) {
      if (empty($row)) {
        return array(); //There's a global auth option
      }
    }

    return $result;
  }

}