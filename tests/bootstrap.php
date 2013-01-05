<?php
set_include_path(get_include_path().';'.__DIR__.'/../src/');
date_default_timezone_set('Europe/London');
/**
 * Sets up the autoloader for Unit Tests
 */
require_once __DIR__.'/../src/Interfaces/Singleton.php';
require_once __DIR__.'/../src/Interfaces/CacheProvider.php';
require_once __DIR__.'/../src/Interfaces/IServiceAPI.php';
require_once __DIR__.'/../src/Interfaces/MyURY_DataSource.php';
require_once __DIR__.'/../src/Interfaces/TemplateEngine.php';
require_once __DIR__.'/../src/Classes/Config.php';
spl_autoload_register(function($class){
  $class .= '.php';
  if (file_exists(__DIR__.'/../src/Classes/MyURY/'.$class)) {
    require_once __DIR__.'/../src/Classes/MyURY/'.$class;
  }
  elseif (file_exists(__DIR__.'/../src/Classes/ServiceAPI/'.$class)) {
    require_once __DIR__.'/../src/Classes/ServiceAPI/'.$class;
  }
  elseif (file_exists(__DIR__.'/../src/Classes/'.$class)) {
    require_once __DIR__.'/../src/Classes/'.$class;
  }
});