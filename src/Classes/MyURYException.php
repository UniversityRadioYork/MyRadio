<?php
/**
 * Extends the standard Exception class to provide additional functionality
 * and logging
 *
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 22052012
 * @package MyURY_Core
 */
class MyURYException extends Exception {
  const FATAL = -1;
  /**
   * Extends the default session by enabling useful output
   * @param String $message A nice message explaining what is going on
   * @param int $code A number representing the problem. -1 Indicates fatal.
   * @param Exception $previous 
   */
  public function __construct($message, $code = 0, Exception $previous = null) {
    //Set up the Exception
    parent::__construct($message, $code, $previous);
    if (class_exists('Config')) {
      //Configuration is available, use this to decide what to do
      if (Config::$display_errors) {
        //Output to the browser
        echo "MyURY has encountered a problem processing this request.
          <table>
            <tr><td>Message</td><td>{$this->getMessage()}</td></tr>
            <tr><td>Location</td><td>{$this->getFile()}:{$this->getLine()}</td></tr>
            <tr><td>Trace</td><td>".nl2br($this->getTraceAsString())."</td></tr>
          </table>";
      }
    }
    if ($code === -1) {
      echo '<div class="ui-state-error">A fatal error has occured that has prevented MyURY from performing the action you requested.</div>';
    }
  }
}