<?php

/**
 * This file provides the MyRadioException class for MyRadio
 * @package MyRadio_Core
 */

namespace MyRadio;

use \MyRadio\MyRadio\CoreUtils;

/**
 * Extends the standard Exception class to provide additional functionality
 * and logging
 *
 * @author  Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130711
 * @package MyRadio_Core
 */
class MyRadioException extends \RuntimeException
{
    const FATAL = -1;

    private static $count = 0;
    private $error;
    private $trace;
    private $traceStr;

    /**
     * Extends the default session by enabling useful output
     * @param String     $message  A nice message explaining what is going on
     * @param int        $code     A number representing the problem. -1 Indicates fatal.
     * @param \Exception $previous
     */
    public function __construct($message, $code = 500, \Exception $previous = null)
    {
        parent::__construct((string) $message, (int) $code, $previous);

        self::$count++;
        if (self::$count > Config::$exception_limit) {
            trigger_error('Exception limit exceeded. Futher exceptions will not be reported.');
            return;
        }

        $this->trace = $this->getTrace();
        $this->traceStr = $this->getTraceAsString();
        if ($previous) {
            $this->trace = array_merge($this->trace, $previous->getTrace());
            $this->traceStr .= "\n\n".$this->getTraceAsString();
        }

        //Set up the Exception
        $this->error = "<p>MyRadio has encountered a problem processing this request.</p>
                <table class='errortable' style='color:#633'>
                  <tr><td>Message: </td><td>{$this->getMessage()}</td></tr>
                  <tr><td>Location: </td><td>{$this->getFile()}:{$this->getLine()}</td></tr>
                  <tr><td>Trace: </td><td>" . nl2br($this->traceStr) . "</td></tr>
                </table>";
    }

    /**
    * Called when the exception is not caught
    */
    public function uncaught()
    {
        $silent = (defined('SILENT_EXCEPTIONS') && SILENT_EXCEPTIONS);

        if (class_exists('\MyRadio\Config')) {
            $is_ajax = (isset($_SERVER['HTTP_X_REQUESTED_WITH'])
                    && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
                || empty($_SERVER['REMOTE_ADDR']);

            if (Config::$email_exceptions && class_exists('\MyRadio\MyRadioEmail') && $this->code !== 400) {
                MyRadioEmail::sendEmailToComputing(
                    '[MyRadio] Exception Thrown',
                    'Code: ' . $this->code . "\r\n\r\n"
                    . 'Message: ' . $this->message . "\r\n\r\n"
                    . "Trace: \r\n" . $this->traceStr . "\r\n\r\n"
                    . "Request: \r\n" . CoreUtils::getRequestInfo() . "\r\n\r\n"
                    . "Session: \r\n"
                    . (isset($_SESSION) ? print_r($_SESSION, true) : '')
                );
            }

            if (Config::$log_file) {
                // TODO make this create the dir - maybe use error_log?
                file_put_contents(
                    Config::$log_file,
                    CoreUtils::getTimestamp() . '[' . $this->code . '] ' . $this->message . "\n" . $this->traceStr,
                    FILE_APPEND
                );
            }

            //Configuration is available, use this to decide what to do
            if (!$silent
                && Config::$display_errors
                || (class_exists('\MyRadio\MyRadio\CoreUtils')
                && defined('AUTH_SHOWERRORS')
                && CoreUtils::hasPermission(AUTH_SHOWERRORS))
            ) {
                if ($is_ajax) {
                    //This is an Ajax/CLI request. Return JSON
                    header('HTTP/1.1 ' . $this->code . ' Internal Server Error');
                    header('Content-Type: application/json');
                    echo json_encode(
                        [
                        'status' => 'MyRadioException',
                        'error' => $this->message,
                        'code' => $this->code,
                        'trace' => $this->trace
                        ]
                    );
                } else {
                    //Output to the browser
                    header('HTTP/1.1 ' . $this->code . ' Internal Server Error');

                    if (class_exists('\MyRadio\MyRadio\CoreUtils') && !headers_sent()) {
                        //We can use a pretty full-page output
                        $twig = CoreUtils::getTemplateObject();
                        $twig->setTemplate('error.twig')
                            ->addVariable('serviceName', 'Error')
                            ->addVariable('title', 'Internal Server Error')
                            ->addVariable('body', $this->error)
                            ->addVariable('uri', $_SERVER['REQUEST_URI'])
                            ->render();
                    } else {
                        echo $this->error;
                    }
                }
            } elseif (!$silent) {
                if ($is_ajax) {
                    //This is an Ajax/CLI request. Return JSON
                    header('HTTP/1.1 ' . $this->code . ' Internal Server Error');
                    header('Content-Type: application/json');
                    echo json_encode(
                        [
                        'status' => 'MyRadioError',
                        'error' => $this->message,
                        'code' => $this->code
                        ]
                    );
                    //Stick the details in the session in case the user wants to report it
                    $_SESSION['last_ajax_error'] = [$this->error, $this->code, $this->trace];
                } else {
                    $error = '<div class="errortable">'
                        .'<p>Sorry, we have encountered an error and are unable to continue. Please try again later.</p>'
                        .'<p>' . $this->message . '</p>'
                        .'<p>Computing Team have been notified.</p>'
                        .'</div>';
                    //Output limited info to the browser
                    header('HTTP/1.1 ' . $this->code . ' Internal Server Error');

                    if (class_exists('\MyRadio\MyRadio\CoreUtils') && !headers_sent()) {
                        //We can use a pretty full-page output
                        $twig = CoreUtils::getTemplateObject();
                        $twig->setTemplate('error.twig')
                            ->addVariable('title', '')
                            ->addVariable('body', $error)
                            ->addVariable('uri', $_SERVER['REQUEST_URI'])
                            ->render();
                    } else {
                        echo $error;
                    }
                }
            }
        } elseif (!$silent) {
            echo 'MyRadio is unavailable at the moment. Please try again later. If the problem persists, contact support.';
        }
    }

    /**
     * Get the number of MyRadioExceptions that have been fired.
     * @return int
     */
    public static function getExceptionCount()
    {
        return self::$count;
    }

    public static function resetExceptionCount()
    {
        self::$count = 0;
    }
}
