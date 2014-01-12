<?php
/**
 * A standard interface for all ServiceAPI Classes. Implements the following
 * base functionality:
 * - Initialises a database connection on instantiation
 * - Initialises a cache connection on instantiation
 * - Restores the database and cache connections on restore
 * - A static factory to create an instance of the ServiceAPI Object
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130808
 * @package MyRadio_Core
 */
interface IServiceAPI {
  /**
   * Reestablishes the database connection after being Cached 
   */
  function __wakeup();
  
  function toDataSource($full = false);
  
  /**
   * Static Factory method to setup an instance of a ServiceAPI Object
   */
  static function getInstance($serviceObjectId);
}