<?php
/**
 * Stores configuration settings
 *
 * @author Lloyd Wallis <lpw@ury.york.ac.uk>
 * @version 22052012
 */
final class Config {
  public static $db_hostname = 'dbserver.ury.york.ac.uk';
  public static $db_user     = 'web';
  public static $db_pass     = 'ChanceRecordFactHappen';
  
  public static $cache_enable   = true;
  public static $cache_provider = 'APCProvider';
  
  public static $display_errors = true;
  
  public static $template_debug = true;
  
  
  private function __construct() {} //Inhibit creating objects
}
