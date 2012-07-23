<?php
/**
 * This is the bootstrap for the MyURY Service. It sets up things required only for the MyURY Service itself that aren't
 * needed by the MyURY Core. Currently, this is only an additional autoloader for MyURY Classes
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 21072012
 * @package MyURY_Core
 */

/**
 * Register a MyURY Autoloader 
 */
spl_autoload_register(function($class){
  $class .= '.php';
  if (file_exists(__DIR__.'/../../Classes/MyURY/'.$class)) {
    require_once 'Classes/MyURY/'.$class;
  }
});