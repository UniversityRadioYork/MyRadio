<?php
/**
 * 
 * @todo Proper Documentation
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