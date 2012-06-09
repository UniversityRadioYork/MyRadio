<?php
/**
 * A standard interface for all ServiceAPI Classes. Implements the following
 * base functionality:
 * - Initialises a database connection on instantiation
 * - Initialises a cache connection on instantiation
 * - Restores the database and cache connections on restore
 * - A static factory to create an instance of the ServiceAPI Object
 * @author lpw
 */
interface ServiceAPI {
  protected $db;
  protected $cache;
  
  /**
   * Initialises the database instance 
   */
  function initDB();
  
  /**
   * Initialises the Cache instance 
   */
  function initCache();
  
  /**
   * Reestablishes the database connection after being Cached 
   */
  function __wakeup();
  
  /**
   * Static Factory method to setup an instance of a ServiceAPI Object
   */
  static function getInstance($serviceObjectId = -1);
}