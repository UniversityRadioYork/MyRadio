<?php

/**
 * This file provides the MyRadioException class for MyRadio
 * @package MyRadio_Core
 */

namespace MyRadio;

/**
 * Extends the standard Exception class to provide additional functionality
 * and logging
 *
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130711
 * @package MyRadio_Core
 */
class MyRadioException extends \RuntimeException
{
    const FATAL = -1;

    private static $count = 0;

    /**
     * Extends the default session by enabling useful output
     * @param String $message A nice message explaining what is going on
     * @param int $code A number representing the problem. -1 Indicates fatal.
     * @param \Exception $previous
     */
    public function __construct($message, $code = 500, \Exception $previous = null)
    {
        self::$count++;
        if (self::$count > Config::$exception_limit) {
            trigger_error('Exception limit exceeded. Futher exceptions will not be reported.');

            return;
        }

        $trace = $this->getTrace();
        $traceStr = $this->getTraceAsString();
        if ($previous) {
            $trace = array_merge($trace, $previous->getTrace());
            $traceStr .= "\n\n".$this->getTraceAsString();
        }

        parent::__construct((string) $message, (int) $code, $previous);

        if (defined('SILENT_EXCEPTIONS') && SILENT_EXCEPTIONS) {
            return;
        }

        //Set up the Exception
        $error = "<p>MyRadio has encountered a problem processing this request.</p>
                <table class='errortable' style='color:#633'>
                  <tr><td>Message</td><td>{$this->getMessage()}</td></tr>
                  <tr><td>Location</td><td>{$this->getFile()}:{$this->getLine()}</td></tr>
                  <tr><td>Trace</td><td>" . nl2br($traceStr) . "</td></tr>
                </table>";

        if (class_exists('Config')) {
            if (Config::$email_exceptions && class_exists('MyRadioEmail') && $code !== 400) {
                MyRadioEmail::sendEmailToComputing(
                    '[MyRadio] Exception Thrown',
                    $error . "\r\n" . $message . "\r\n"
                    . (isset($_SESSION) ? print_r($_SESSION, true) : '')
                    . "\r\n" . CoreUtils::getRequestInfo()
                );
            }
            //Configuration is available, use this to decide what to do
            if (Config::$display_errors
                or (class_exists('CoreUtils')
                && CoreUtils::hasPermission(AUTH_SHOWERRORS))
            ) {
                if ((isset($_SERVER['HTTP_X_REQUESTED_WITH'])
                    && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
                    or empty($_SERVER['REMOTE_ADDR'])
                ) {
                    //This is an Ajax/CLI request. Return JSON
                    header('HTTP/1.1 ' . $code . ' Internal Server Error');
                    header('Content-Type: text/json');
                    echo json_encode([
                        'status' => 'MyRadioException',
                        'error' => $message,
                        'code' => $code,
                        'trace' => $trace
                    ]);
                } else {
                    //Output to the browser
                    header('HTTP/1.1 ' . $code . ' Internal Server Error');

                    if (class_exists('CoreUtils') && !headers_sent()) {
                        //We can use a pretty full-page output
                        $twig = CoreUtils::getTemplateObject();
                        $twig->setTemplate('error.twig')
                            ->addVariable('serviceName', 'Error')
                            ->addVariable('title', 'Internal Server Error')
                            ->addVariable('body', $error)
                            ->addVariable('uri', $_SERVER['REQUEST_URI'])
                            ->render();
                    } else {
                        echo $error;
                    }
                }
            } else {
                $error = '<div class="errortable"><strong>' . $this->getMessage() . '</strong>'
                        . '<p>A fatal error has occured that has prevented MyRadio from performing the action you requested. '
                        . 'The computing team have been notified.</p></div>';
                //Output limited info to the browser
                header('HTTP/1.1 ' . $code . ' Internal Server Error');

                if (class_exists('CoreUtils') && !headers_sent()) {
                    //We can use a pretty full-page output
                    $twig = CoreUtils::getTemplateObject();
                    $twig->setTemplate('error.twig')
                        ->addVariable('serviceName', 'Error')
                        ->addVariable('title', 'Internal Server Error')
                        ->addVariable('body', $error)
                        ->addVariable('uri', $_SERVER['REQUEST_URI'])
                        ->render();
                } else {
                    echo $error;
                }
            }
        } else {
            echo 'A fatal error has occured that has prevented MyRadio from performing the action you requested. Please contact computing@ury.org.uk.';
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
