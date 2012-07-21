<?php
/**
 * Stores configuration settings
 *
 * @author Lloyd Wallis <lpw@ury.york.ac.uk>
 * @version 22052012
 */
final class Config {
  public static $db_hostname    = 'dbserver.ury.york.ac.uk';
  public static $db_user        = 'web';
  public static $db_pass        = 'ChanceRecordFactHappen';
  
  public static $base_url       = '//ury.york.ac.uk/myury/';
  public static $rewrite_url    = false;
  
  public static $cache_enable   = false;
  public static $cache_provider = 'APCProvider';
  
  public static $display_errors = true;
  
  public static $template_debug = true;
  
  //The default maximum number of results from an ajax autocomplete query
  public static $ajax_limit_default = 10;
  
  public static $photo_joined = 1;
  public static $photo_officership_get = 2;
  public static $photo_officership_down = 3;
  public static $photo_show_get = 4;
  public static $photo_award_get = 5;
  
  
  private function __construct() {} //Inhibit creating objects
}
