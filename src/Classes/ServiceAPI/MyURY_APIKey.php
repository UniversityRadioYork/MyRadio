<?php
/**
 * Provides the MyURY_APIKey class for MyURY
 * @package MyURY_API
 */

/**
 * The APIKey Class provies information and management of API Keys for the MyURY
 * REST API.
 * 
 * @version 20130802
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @package MyURY_API
 * @uses \Database
 */
class MyURY_APIKey extends ServiceAPI {

  /**
   * Singleton store.
   * @var MyURY_APIKey[]
   */
  private static $keys = [];
  
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
   * Get the object for the given API Key
   * @param String $key
   * @return MyURY_APIKey
   * @throws MyURYException
   */
  public static function getInstance($key = null) {
    self::wakeup();
    if ($key === null) {
      throw new MyURYException('Invalid API Key', 400);
    }

    if (!isset(self::$keys[$key])) {
      self::$keys[$key] = new self($key);
    }

    return self::$keys[$key];
  }
  
  /**
   * Construct the API Key Object
   * @param String $key
   */
  private function __construct($key) {
    $this->key = $key;
    $this->permissions = self::$db->fetch_column('SELECT typeid FROM myury.api_key_auth WHERE key_string=$1',
            array($key));
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
    var_dump($result);
    if ($result === null) {
      echo "NULL";
      return false; //No permissions means the method is not accessible
    }
    
    foreach ($result as $type) {
      if (empty($type)) {
        return true; //NULL permissions allow any key
      }
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
      (method_name=$2 OR method_name IS NULL)',
            array($class, $method));
    
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