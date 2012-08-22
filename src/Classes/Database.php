<?php
/**
 * This file provides the Database class for MyURY
 * @package MyURY_Core
 */

/**
 * This singleton class handles actual database connection
 * 
 * @version 22052012
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @depends Config
 * @package MyURY_Core
 */
class Database {

  /**
   * Stores the singleton instance of the Database object 
   * @var Database
   */
  private static $me;
  /**
   * Stores the resource id of the connection to the PostgreSQL database
   * @var Resource
   */
  private $db;

  /**
   * Constructs the singleton database connector 
   */
  private function __construct() {
    $this->db = pg_connect('host=' . Config::$db_hostname . ' port=5432 dbname=membership
            user=' . Config::$db_user . ' password=' . Config::$db_pass);
    if (!$this->db) {
      //Database isn't working. Throw an EVERYTHING IS BROKEN Exception
      throw new MyURYException('Database Connection Failed!',
              MyURYException::FATAL);
    }
  }

  /**
   * Generic function that just runs a pg_query_params
   * @param String $sql The query string to execute
   * @param Array $params Parameters for the query
   * @param bool $rollback If true, an error will automatically execute a rollback before throwing an Exception
   * Use for transactions.
   * @return A pg result reference
   * @throws MyURYException 
   */
  public function query($sql, $params = array(), $rollback = false) {
    $result = pg_query_params($this->db, $sql, $params);
    if (!$result) {
      if ($rollback) {
        pg_query($this->db, 'ROLLBACK');
      }
      throw new MyURYException('Query failure: ' . $sql . '<br />'
              . pg_errormessage($this->db));
    }
    return $result;
  }

  /**
   * Equates to a pg_num_rows($result)
   * @param Resource $result a reference to a postgres result set
   * @return int The number of rose in the result set
   */
  public function num_rows($result) {
    return pg_num_rows($result);
  }

  /**
   * The most commonly used database function
   * Equates to a pg_fetch_all(pg_query)
   * @param String|Resource $sql The query string to execute
   * or a psql result resource
   * @param Array $params Parameters for the query
   * @return Array An array of result rows (potentially empty)
   * @throws MyURYException 
   */
  public function fetch_all($sql, $params = array()) {
    if (is_resource($sql)) {
      return pg_fetch_all($sql);
    } elseif (is_string($sql)) {
      try {
        $result = $this->query($sql, $params);
      } catch (MyURYException $e) {
        return array();
      }
      if (pg_num_rows($result) === 0)
        return array();
      return pg_fetch_all($result);
    } else {
      throw new MyURYException('Invalid Request for $sql');
    }
  }

  /**
   * Equates to a pg_fetch_assoc(pg_query). Returns the first row
   * @param String $sql The query string to execute
   * @param Array $params Paramaters for the query
   * @return Array The requested result row, or an empty array on failure
   * @throws MyURYException 
   */
  public function fetch_one($sql, $params = array()) {
    try {
      $result = $this->query($sql, $params);
    } catch (MyURYException $e) {
      return array();
    }
    return pg_fetch_assoc($result);
  }

  /**
   * Equates to a pg_fetch_all_columns(pg_query,0). Returns all first column entries
   * @param String $sql The query string to execute
   * @param Array $params Paramaters for the query
   * @return Array The requested result column, or an empty array on failure
   * @throws MyURYException 
   */
  public function fetch_column($sql, $params = array()) {
    try {
      $result = $this->query($sql, $params);
    } catch (MyURYException $e) {
      return array();
    }
    if (pg_num_rows($result) === 0)
      return array();
    return pg_fetch_all_columns($result, 0);
  }

  /**
   * Used to create the object, or return a reference to it if it already exists
   * @return Database One of these things
   */
  public static function getInstance() {
    if (!self::$me) {
      self::$me = new self();
    }
    return self::$me;
  }

  /**
   * Prevent copies being unintentionally made
   * @throws MyURYException
   */
  public function __clone() {
    throw new MyURYException('Attempted to clone a singleton');
  }
  
  /**
   * Converts a postgresql array to a php array
   */
  public function decodeArray($data) {
    return json_decode(str_replace(array('{','}'),array('[',']'),$data));
  }

}