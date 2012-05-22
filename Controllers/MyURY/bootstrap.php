<?php
/**
 * Register a MyURY Autoloader 
 */
spl_autoload_register(function($class){
  $class .= '.php';
  if (file_exists(__DIR__.'/../../Classes/MyURY/'.$class)) {
    require_once 'Classes/MyURY/'.$class;
  }
});