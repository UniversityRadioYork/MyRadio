<?php
/*
 * Provides the Scheduler Common class for MyURY
 * @package MyURY_Scheduler
 */

/*
 * The Scheduler_Common class is used to provide common resources for the MyURY Scheduler classes
 * @version 04092012
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @package MyURY_Scheduler
 * @uses \Database
 * 
 */
abstract class MyURY_Scheduler_Common extends ServiceAPI {
  protected static $metadata_keys = array();
  /**
   * Gets the id for the string representation of a type of metadata
   */
  public static function getMetadataKey($string) {
    self::cacheMetadataKeys();
    if (!isset(self::$metadata_keys[$string])) {
      throw new MyURYException('Metadata Key ' . $string . ' does not exist');
    }
    return self::$metadata_keys[$string]['id'];
  }

  /**
   * Gets whether the type of metadata is allowed to exist more than once
   */
  public static function isMetadataMultiple($id) {
    self::cacheMetadataKeys();
    foreach (self::$metadata_keys as $key) {
      if ($key['id'] == $id)
        return $key['multiple'];
    }
    throw new MyURYException('Metadata Key ID ' . $string . ' does not exist');
  }

  protected static function cacheMetadataKeys() {
    if (empty(self::$metadata_keys)) {
      self::initDB();
      $r = self::$db->fetch_all('SELECT metadata_key_id AS id, name, allow_multiple AS multiple FROM public.metadata_key');
      foreach ($r as $key) {
        self::$metadata_keys[$key['name']]['id'] = (int) $key['id'];
        self::$metadata_keys[$key['name']]['multiple'] = ($key['multiple'] === 't');
      }
    }
  }
  
  protected static function getCreditName($credit_id) {
    self::initDB();
    $r = self::$db->fetch_one('SELECT name FROM people.credit_type WHERE credit_type_id=$1 LIMIT 1', array((int)$credit_id));
    if (empty($r)) return 'Contrib';
    return $r['name'];
  }
}