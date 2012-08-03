<?php
/**
 * This file provides the MyURYError class for MyURY
 * @package MyURY_Core
 */

/**
 * Provides error handling so that php errors can be displayed nicely.
 * 
 * @author Andy Durant <aj@ury.org.uk>
 * @version 03082012
 * @package MyURY_Core
 */
class MyURYError {
  
/**
 * @var array $error_type An array that matches error codes from $errno to a short string which names the error type (such as "User-generated error", or "User-generated warning")
 */
  private static $error_type = array(
    E_ERROR => 'Fatal error',
    E_WARNING => 'Warning',
    E_PARSE => 'Parse error',
    E_NOTICE => 'Notice',
    E_CORE_ERROR => 'Core error',
    E_CORE_WARNING => 'Core warning',
    E_COMPILE_ERROR => 'Compile-time error',
    E_COMPILE_WARNING => 'Compile-time warning',
    E_USER_ERROR => 'User-generated error',
    E_USER_WARNING => 'User-generated warning',
    E_USER_NOTICE => 'User-generated notice',
    E_STRICT => 'Runtime notice',
    E_RECOVERABLE_ERROR => 'Recoverable error'
  );
  /**
   * @var array $php_errorlist An array holding all php errors as arrays of [$error_name,$errstr,$errfile,$errline]
   */
  public static $php_errorlist = array();
  
  /**
   * Places all php errors into the array $php_errorlist
   * @param string $errno A numeric value which corresponds to the type of error (Notice, Fatal Error, User-generated warning, etc).
   * @param string $errstr A string that contains the error message text, ideally including details that identify the cause of the error.
   * @param string $errfile The full local path of the file which has triggered this error (such as /var/www/public_html/badscript.php).
   * @param string $errline The line number where the error was generated (within the file identified by $errfile).
   */
  public static function errorsToArray($errno, $errstr, $errfile, $errline) {
    $error_name = (isset(self::$error_type[$errno]) ? self::$error_type[$errno] : 'Unknown error code');
    $php_error = array(
        'name' => $error_name, 
        'string' => $errstr, 
        'file' => htmlspecialchars($errfile, ENT_NOQUOTES, 'UTF-8'), 
        'line' => $errline);
    array_push(self::$php_errorlist, $php_error);
  }
  /**
   * Logs all php errors into the php log file
   * @param string $errno A numeric value which corresponds to the type of error (Notice, Fatal Error, User-generated warning, etc).
   * @param string $errstr A string that contains the error message text, ideally including details that identify the cause of the error.
   * @param string $errfile The full local path of the file which has triggered this error (such as /var/www/public_html/badscript.php).
   * @param string $errline The line number where the error was generated (within the file identified by $errfile).
   */
  public static function errorsToLog($errno, $errstr, $errfile, $errline) {
    /*
     * Stage one: log the error using PHP's error logger.
     */
    $error_name = (isset(self::$error_type[$errno]) ? self::$error_type[$errno] : 'Unknown error code');

    // Structure the error message in the same way as PHP logs
    // fatal errors, because they'll be saved in the same file.
    $error_message = $error_name.': '.
            $errstr.' in '.
            $errfile.' on line '.$errline;
    error_log($error_message, 0);  // log to PHP_ERROR_LOG file
  }
  /**
   * @todo errorsToEmail() - sends errors to computing@ury on a daily basis, 
   * see http://www.bobulous.org.uk/coding/php-error-handling.html 
   * using a standard MyURYEmail class
   */
  /**
   * @todo handlerError() - the MyURYError class should decide how to actually handle the error
   * handlerError() would deal with it as it saw fit using the previously defined methods
   */
}