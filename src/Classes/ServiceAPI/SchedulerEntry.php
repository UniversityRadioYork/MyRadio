<?php
/**
 * This file provides the SchedulerEntry class for MyURY
 * @package MyURY_Scheduler
 */

/**
 * Object to represent a single Schedule Entry for MyURY Scheduler Module
 * Purely for use as a get/set/construct abstractor
 * 
 * @todo Write this class
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 21072012
 * @package MyURY_Scheduler
 * @todo Write this.
 */
class SchedulerEntry extends ServiceAPI {
  
  /**
   * Nothing yet.
   * @todo write this
   * @param int $id
   */
  function __construct($id) {}
  
  /**
   * Returns a Singleton SchedulerEntry, creating if necessary
   * @param int $id The SchedulerEntry entryid to return
   * @return \self
   * @throws MyURYException If an entryid is the default value
   */
  public static function getInstance($id = 0) {
    if ($id === 0) throw new MyURYException('entryid must be specified');
    return new self((int)$id);
  }
}