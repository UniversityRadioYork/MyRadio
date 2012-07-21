<?php
/**
 * Object to represent a single Schedule Entry for MyURY Scheduler Module
 * Purely for use as a get/set/construct abstractor
 * 
 * @todo Write this class
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 21072012
 * @package MyURY_Scheduler
 */

class SchedulerEntry extends ServiceAPI {
  
  function __construct($id) {}
  
  public static function getInstance($id = 0) {
    if ($id === 0) throw new MyURYException('entryid must be specified');
    return new self((int)$id);
  }
}