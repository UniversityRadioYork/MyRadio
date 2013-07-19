#!/usr/local/bin/php
<?php
/**
 * This is the Daemon Controller - it handles all "background" processing - sending emails, consistency checks
 * and anything else you might like to routinely happen related to MyURY or using the APIs it provides.
 * 
 * This is designed to run on a command line - no auth required! It has one command line option, --once,
 * which is set, will run a single iteration of all the registered background systems, not loop continuously.
 * 
 * The system works by simply reading in all files in Classes/Deamons. It will check the the file is syntactically
 * correct (by passing it to php -l) and then load it. It is expected the file includes a class of the same name as the
 * file, then will call class::isEnabled(), which will enable specific daemons to be disabled.
 * 
 * Enabled Deamons will then have class:run() executed on them, which should execute the desired task once, then return.
 * This controller will deal with recursion and timing.
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 24032013
 * @package MyURY_Deamon
 * @uses \Database
 * @uses \CoreUtils
 */

$path = __DIR__.'/../Classes/Daemons/';
$handle = opendir($path);
if (!$handle) die('PATH DOES NOT EXIST '.$path."\n");
$classes = array();

require_once __DIR__.'/cli_common.php';

//Should this run once or loop forever?
$once = in_array('--once', $argv);

//Load all classes that should be run
while (false !== ($file = readdir($handle))) {
  if ($file === '.' or $file === '..') continue;
  //Is the file valid PHP?
  system('php -l '.$path.$file, $result);
  if ($result !== 0) {
    echo "Not checking $file - Parse Error\n";
  } else {
    require $path.$file;
    $class = str_replace('.php','',$file);
    if (!class_exists($class)) {
      echo "Daemon does not exist - $class\n";
    } else {
      if (!$class::isEnabled()) {
        echo "Daemon $class is not enabled\n";
      } else {
        $classes[] = $class;
      }
    }
  }
}

if (empty($classes)) {
  die("No daemons to execute\n");
}

//Run each
while (true) {
  foreach ($classes as $class) {
    echo "Running $class\n";
    try {
      $class::run();
    } catch (MyURYException $e) {}
    if (!$once) sleep(2);
  }
  
  //Every once in a while, check database connection. If it's lost, routinely try to reconnect.
  if (!Database::getInstance()->status()) {
    echo "CRITICAL: Database server connection lost. Attempting to reconnect...";
    $db_fail_start = time();
    while (!Database::getInstance()->reconnect()) {
      if (time() - $db_fail_start > 900) {
        //Connection has been lost for more than 15 minutes. Give up.
        MyURYEmail::sendEmailToComputing('[MyURY] Background Service Failure', "MyURY's connection to the Database Server has been lost. Attempts to reconnect for the last 15 minutes have proved futile, so the service has stopped.\r\n\Please investigate Database connectivity and restart the service one access is restored.");
      }
      echo "FAILED!\nWill retry in 30 seconds.";
      sleep(30);
    }
    echo "RECONNECTED\n";
  }
  
  if ($once) break;
  
  //At the end of an interation, commit a query and error count.
  //This is both nice for statistics, and prevents an entry of several tens of thousands when the server restarts :)
  CoreUtils::shutdown();
  Database::getInstance()->resetCounter();
  MyURYException::resetExceptionCount();
  MyURYError::resetErrorCount();
}