<?php

/**
 * This file provides the MyRadioException class for MyRadio.
 */
namespace MyRadio;

use GraphQL\Error\ClientAware;
use MyRadio\MyRadio\AuthUtils;
use MyRadio\MyRadio\CoreUtils;

/**
 * Extends the standard Exception class to provide additional functionality
 * and logging.
 */
class MyRadioException extends \RuntimeException implements ClientAware
{
    const FATAL = -1;

    private static $count = 0;
    private $error;
    private $trace;
    private $traceStr;

    public function getCodeName()
    {
        switch ($this->code) {
            case 400:
                return 'Bad Request';
            case 401:
                return 'Authentication Required';
            case 403:
                return 'Unauthorized';
            case 404:
                return 'File Not Found';
            case 405:
                return 'Method Not Allowed';
            case 418:
                return 'I\'m a Teapot';
            case 500:
                return 'Internal Server Error';
        }
    }

    /**
     * Extends the default session by enabling useful output.
     *
     * @param string     $message  A nice message explaining what is going on
     * @param int        $code     A number representing the problem. -1 Indicates fatal.
     * @param \Exception $previous
     */
    public function __construct($message, $code = 500, \Exception $previous = null)
    {
        parent::__construct((string) $message, (int) $code, $previous);

        ++self::$count;
        if (self::$count > Config::$exception_limit) {
            trigger_error("Exception limit exceeded. Further exceptions will not be reported.");
            return;
        }

        $this->trace = $this->getTrace();
        $this->traceStr = $this->getTraceAsString();
        if ($previous) {
            $this->trace = array_merge($this->trace, $previous->getTrace());
            $this->traceStr .= "\n\n".$this->getTraceAsString();
        }

        //Set up the Exception
        if ($code === 403) {
            $this->error = "<p>I'm sorry, but you don't have permission to access this page.</p>
                            <p>{$this->getMessage()}</p>";
        } else {
            $this->error = "<p>MyRadio has encountered a problem processing this request.</p>
                <table class='errortable' style='color:#633'>
                  <tr><td>Message: </td><td>{$this->getMessage()}</td></tr>
                  <tr><td>Location: </td><td>{$this->getFile()}:{$this->getLine()}</td></tr>
                  <tr><td>Trace: </td><td>" . nl2br($this->traceStr) . '</td></tr>
                </table>';
        }
    }

    /**
     * Called when the exception is not caught.
     */
    public function uncaught()
    {
        $silent = (defined('SILENT_EXCEPTIONS') && SILENT_EXCEPTIONS);

        if (class_exists('\MyRadio\Config')) {
            $is_ajax = (isset($_SERVER['HTTP_X_REQUESTED_WITH'])
                    && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
                || empty($_SERVER['REMOTE_ADDR'])
                || (defined('JSON_DEBUG') && JSON_DEBUG);

            if (Config::$email_exceptions
                && class_exists('\MyRadio\MyRadioEmail')
                && $this->code !== 400
                && $this->code !== 401
                && $this->code !== 403
            ) {
                MyRadioEmail::sendEmailToComputing(
                    '[MyRadio] Exception Thrown',
                    'Code: '.$this->code."\r\n\r\n"
                    .'Message: '.$this->message."\r\n\r\n"
                    ."Trace: \r\n".$this->traceStr."\r\n\r\n"
                    ."Request: \r\n".CoreUtils::getRequestInfo()."\r\n\r\n"
                    ."RequestURI: \r\n".$_SERVER['REQUEST_URI']."\r\n\r\n"
                    ."Session: \r\n"
                    .(isset($_SESSION) ? print_r($_SESSION, true) : '')
                );
            }

            if (Config::$log_file) {
                // TODO make this create the dir - maybe use error_log?
                file_put_contents(
                    Config::$log_file,
                    CoreUtils::getTimestamp().'['.$this->code.'] '.$this->message."\n".$this->traceStr."\n\n",
                    FILE_APPEND
                );
            }

            //Configuration is available, use this to decide what to do
            if (false) {
                if ($is_ajax) {
                    //This is an Ajax/CLI request. Return JSON
                    header('HTTP/1.1 '.$this->code.' '.$this->getCodeName());
                    header('Content-Type: application/json');
                    echo json_encode(
                        [
                        'status' => 'MyRadioException',
                        'error' => $this->message,
                        'code' => $this->code,
                        'trace' => $this->trace,
                        ]
                    );
                } else {
                    //Output to the browser
                    header('HTTP/1.1 '.$this->code.' '.$this->getCodeName());

                    if (class_exists('\MyRadio\MyRadio\CoreUtils') && !headers_sent()) {
                        //We can use a pretty full-page output
                        $twig = CoreUtils::getTemplateObject();
                        $twig->setTemplate('error.twig')
                            ->addVariable('serviceName', 'Error')
                            ->addVariable('title', $this->getCodeName())
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
                    header('HTTP/1.1 '.$this->code.' '.$this->getCodeName());
                    header('Content-Type: application/json');
                    echo json_encode(
                        [
                        'status' => 'MyRadioError',
                        'error' => $this->message,
                        'code' => $this->code,
                        ]
                    );
                    //Stick the details in the session in case the user wants to report it
                    $_SESSION['last_ajax_error'] = [$this->error, $this->code, $this->trace];
                } else {
                    $error = '<div class="errortable">'
                        .'<p>Sorry, we encountered an error and are unable to continue. Please try again later.</p>'
                        .'<p>'.$this->message.'</p>'
                        .'<p>Computing Team have been notified.</p>'
                        .'</div>';
                    //Output limited info to the browser
                    header('HTTP/1.1 '.$this->code.' '.$this->getCodeName());

                    if (class_exists('\MyRadio\MyRadio\CoreUtils') && !headers_sent()) {
                        //We can use a pretty full-page output
                        $twig = CoreUtils::getTemplateObject();
                        $twig->setTemplate('error.twig')
                            ->addVariable('title', $this->getCodeName())
                            ->addVariable('body', $this->error)
                            ->addVariable('uri', $_SERVER['REQUEST_URI'])
                            ->render();
                    } else {
                        echo $error;
                    }
                }
            }
        } elseif (!$silent) {
            echo 'MyRadio is unavailable at the moment. '
                .'Please try again later. If the problem persists, contact support.';
        }
    }

    public static function resetExceptionCount()
    {
        self::$count = 0;
    }

    public function isClientSafe()
    {
        return $this->code < 500;
    }

    public function getCategory()
    {
        return 'oh_dear';
    }
}
