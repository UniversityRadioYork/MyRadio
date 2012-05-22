<?php

/**
 * This singleton class handles actual database connection
 * 
 * @version 22052012
 * @author Lloyd Wallis <lpw@ury.york.ac.uk>
 * @depends Config
 */
class Database {
  /**
   * @var Stores the instance of the Database object 
   */
  private static $me;
  private $db;
  
  /**
   * Constructs the singleton database connector 
   */
  private function __construct() {
    $this->db = pg_connect("host={Config::$db_hostname} port=5432 dbname=membership
            user={Config::$db_user} password={Config::$db_pass}");
    if (!$this->db) {
      //Database isn't working. Throw an EVERYTHING IS BROKEN Exception
      throw new MyURYException('Database Connection Failed!',
              MyURYException::FATAL);
    }
  }
  
  /**
   * The most commonly used database function
   * Equates to a pg_fetch_all(pg_query)
   * @param String $sql The query string to execute
   * @param Array $params Paramaters for the query
   * @return Array An array of result rows (potentially empty)
   * @throws MyURYException 
   */
  public function fetch_all($sql, $params = array()) {
    $result = pg_query_params($this->db, $sql, $params);
    if (!$result) {
      throw new MyURYException('Query failure: '.$sql.'<br />'
              .pg_errormessage($this->db));
      return array();
    }
    return pg_fetch_all($result);
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
  
  public function __clone() {
    throw new MyURYException('Attempted to clone a singleton');
  }
}