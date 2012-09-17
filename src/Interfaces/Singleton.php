<?php
/**
 * Provides a standard layout for all URY Singletons
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 21072012
 * @package MyURY_Core
 */
interface Singleton extends MyURY_DataSource {
  public static function getInstance();
}
