<?php

/**
 * This file provides the MyURYException class for MyURY
 * @package MyURY_Core
 */

/**
 * Extends the standard Exception class to provide additional functionality
 * and logging
 *
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 22052012
 * @package MyURY_Core
 */
class MyURYException extends RuntimeException {

  const FATAL = -1;

  /**
   * Extends the default session by enabling useful output
   * @param String $message A nice message explaining what is going on
   * @param int $code A number representing the problem. -1 Indicates fatal.
   * @param \Exception $previous 
   */
  public function __construct($message, $code = 500, Exception $previous = null) {
    //Set up the Exception
    parent::__construct($message, $code, $previous);
    if (class_exists('Config')) {
      //Configuration is available, use this to decide what to do
      if (Config::$display_errors or (class_exists('CoreUtils') && CoreUtils::hasPermission(AUTH_SHOWERRORS))) {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
          //This is an Ajax request. Return JSON
          header('HTTP/1.1 ' . $code . ' Internal Server Error');
          header('Content-Type: text/json');
          echo json_encode(array(
              'status' => 'MyURYException',
              'error' => $message,
              'code' => $code,
              'trace' => $this->getTrace()
              ));
        } else {
          //Output to the browser
          header('HTTP/1.1 ' . $code . ' Internal Server Error');
          $error = "<p>MyURY has encountered a problem processing this request.</p>
            <table class='errortable' style='color:#CCC'>
              <tr><td>Message</td><td>{$this->getMessage()}</td></tr>
              <tr><td>Location</td><td>{$this->getFile()}:{$this->getLine()}</td></tr>
              <tr><td>Trace</td><td>" . nl2br($this->getTraceAsString()) . "</td></tr>
            </table>";

          if (class_exists('CoreUtils') && !headers_sent()) {
            //We can use a pretty full-page output
            $twig = CoreUtils::getTemplateObject();
            $twig->setTemplate('error.twig')
                    ->addVariable('serviceName', 'Error')
                    ->addVariable('serviceVersion', $GLOBALS['service_version'])
                    ->addVariable('title', 'Internal Server Error')
                    ->addVariable('heading', 'Internal Server Error')
                    ->addVariable('body', $error)
                    ->addVariable('uri', $_SERVER['REQUEST_URI'])
                    ->render();
          } else {
            echo $error;
          }
        }
      }
      if (Config::$email_exceptions && class_exists('MyURYEmail')) {
        MyURYEmail::sendEmailToComputing('Exception Thrown', $error."\r\n".$message."\r\n".print_r($GLOBALS,true));
      }
    }
    if ($code === self::FATAL) {
      echo '<div class="ui-state-error">A fatal error has occured that has prevented MyURY from performing the action you requested.</div>';
    }
  }

}