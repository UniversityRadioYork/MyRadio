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
 * @version 20130720
 * @package MyURY_Deamon
 * @uses \Database
 * @uses \CoreUtils
 * 
 * @todo Make this not use echo in various Daemons
 * @todo Install the pcntl extension on thunderhorn
 */

$log_level = 2; //0: Critical, 1: Important, 2: Run Process, 3: Info
$bin = '/usr/local/bin/php'; //Change this to the PHP path for the local system
function dlog($x, $level = 3) {
  if ($level == 0) {
    //Write to stderr
    $f = fopen('php://stderr', 'w');
    fwrite($f, $x);
    fclose($f);
  }
  if ($GLOBALS['log_level'] >= 3)
    echo $x."\n";
}

//Gracefully handle stop requests
function signal_handler($signo) {
  switch ($signo) {
    case SIGTERM:
      //Shutdown
      dlog('Caught SIGTERM. Shutting down after this loop.', 1);
      $GLOBALS['once'] = true; //This will kill after next iteration
  }
}
//Is the extension installed?
if (function_exists('pcntl_signal')) {
  pcntl_signal(SIGTERM, "signal_handler");
}
chdir(__DIR__);

//Okay, we're done setting up service stuff now.
$path = '../Classes/Daemons/';
$handle = opendir($path);
if (!$handle) die('PATH DOES NOT EXIST '.$path."\n");
$classes = array();

require_once 'cli_common.php';

//Should this run once or loop forever?
$once = in_array('--once', $argv);

//Load all classes that should be run
while (false !== ($file = readdir($handle))) {
  if ($file === '.' or $file === '..') continue;
  //Is the file valid PHP?
  system($bin.' -l '.$path.$file, $result);
  if ($result !== 0) {
    dlog('Not checking '.$file.' - Parse Error', 1);
  } else {
    require $path.$file;
    $class = str_replace('.php','',$file);
    if (!class_exists($class)) {
      echo dlog('Daemon does not exist -'. $class, 1);
    } else {
      if (!$class::isEnabled()) {
        dlog('Daemon '. $class . ' is not enabled!', 2);
      } else {
        $classes[] = $class;
      }
    }
  }
}

if (empty($classes)) {
  dlog('No daemons to execute', 0);
  exit;
}

//Run each
while (true) {
  foreach ($classes as $class) {
    dlog('Running '.$class, 2);
    try {
      $class::run();
    } catch (MyURYException $e) {}
    if (!$once) sleep(2);
  }
  
  //Every once in a while, check database connection. If it's lost, routinely try to reconnect.
  if (!Database::getInstance()->status()) {
    dlog('CRITICAL: Database server connection lost. Attempting to reconnect...', 0);
    $db_fail_start = time();
    while (!Database::getInstance()->reconnect()) {
      if (time() - $db_fail_start > 900) {
        //Connection has been lost for more than 15 minutes. Give up.
        MyURYEmail::sendEmailToComputing('[MyURY] Background Service Failure', "MyURY's connection to the Database Server has been lost. Attempts to reconnect for the last 15 minutes have proved futile, so the service has stopped.\r\n\Please investigate Database connectivity and restart the service one access is restored.");
      }
      dlog('FAILED! Will retry in 30 seconds.', 0);
      sleep(30);
    }
    dlog('RECONNECTED', 0);
  }
  
  if ($once) break;
  
  //At the end of an interation, commit a query and error count.
  //This is both nice for statistics, and prevents an entry of several tens of thousands when the server restarts :)
  CoreUtils::shutdown();
  Database::getInstance()->resetCounter();
  MyURYException::resetExceptionCount();
  MyURYError::resetErrorCount();
}