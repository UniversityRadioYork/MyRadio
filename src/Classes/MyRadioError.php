<?php

/**
 * This file provides the MyRadioError class for MyRadio
 * @package MyRadio_Core
 * @todo Andy did this a bit weird....
 */

namespace MyRadio;

/**
 * Provides error handling so that php errors can be displayed nicely.
 *
 * @package MyRadio_Core
 */
class MyRadioError
{
    /**
     * @var int $count Stores the number of errors thrown
     */
    private static $count = 0;

    /**
     * @var array $error_type An array that matches error codes from $errno to
     *  a short string which names the error type (such as
     *  "User-generated error", or "User-generated warning")
     */
    private static $error_type = [
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
    ];

    private static function getErrorName($errno)
    {
        return $error_name = (isset(self::$error_type[$errno]) ?
            self::$error_type[$errno] : 'Unknown error code'
        );
    }

    /**
     * @var array $php_errorlist An array holding all php errors as arrays of
     *  [$error_name,$errstr,$errfile,$errline]
     */
    public static $php_errorlist = [];

    /**
     * Places all php errors into the array $php_errorlist
     * @param string $errno   A numeric value which corresponds to the type of
     *                        error (Notice, Fatal Error, User-generated warning, etc).
     * @param string $errstr  A string that contains the error message text,
     *                        ideally including details that identify the cause of the error.
     * @param string $errfile The full local path of the file which has
     *                        triggered this error (such as /var/www/public_html/badscript.php).
     * @param string $errline The line number where the error was generated
     *                        (within the file identified by $errfile).
     */
    public static function errorsToArray($errno, $errstr, $errfile, $errline)
    {
        if ($errno === E_STRICT or error_reporting() === 0) {
            return;
        }
        $error_name = self::getErrorName($errno);
        $php_error = [
            'name' => $error_name,
            'string' => $errstr,
            'file' => htmlspecialchars($errfile, ENT_NOQUOTES, 'UTF-8'),
            'line' => $errline];
        array_push(self::$php_errorlist, $php_error);
    }

    /**
     * Logs all php errors into the php log file
     * @param string $errno   A numeric value which corresponds to the type of
     *                        error (Notice, Fatal Error, User-generated warning, etc).
     * @param string $errstr  A string that contains the error message text,
     *                        ideally including details that identify the cause of the error.
     * @param string $errfile The full local path of the file which has
     *                        triggered this error (such as /var/www/public_html/badscript.php).
     * @param string $errline The line number where the error was generated
     *                        (within the file identified by $errfile).
     */
    public static function errorsToLog($errno, $errstr, $errfile, $errline)
    {
        /*
         * Stage one: log the error using PHP's error logger.
         */
        $error_name = self::getErrorName($errno);

        // Structure the error message in the same way as PHP logs
        // fatal errors, because they'll be saved in the same file.
        $error_message = $error_name . ': '
            . $errstr . ' in '
            . $errfile . ' on line ' . $errline;
        error_log($error_message);  // log to PHP_ERROR_LOG file
    }

    /**
     * @todo make this throw exceptions rather than echoing fails
     */

    /**
     * Sends the errors to the defined email every 24 hours
     * @param string $errno   A numeric value which corresponds to the type of error
     *                        (Notice, Fatal Error, User-generated warning, etc).
     * @param string $errstr  A string that contains the error message text,
     *                        ideally including details that identify the cause of the error.
     * @param string $errfile The full local path of the file which has triggered
     *                        this error (such as /var/www/public_html/badscript.php).
     * @param string $errline The line number where the error was generated
     *                        (within the file identified by $errfile).
     */
    public static function errorsToEmail($errno, $errstr, $errfile, $errline)
    {
        //If errors have been supressed, don't throw them.
        if (error_reporting() === 0) {
            return;
        }
        //I don't like this error. It is compatible. Maybe report a PHP bug sometime.
        if (strstr($errstr, 'should be compatible with') !== false) {
            return;
        }
        self::$count++; //Increment the error counter

        $errstr = utf8_encode($errstr);
        $error_name = self::getErrorName($errno);
        // Log errors to file for permenance
        self::errorsToLog($errno, $errstr, $errfile, $errline);
        /*
         * Stage two: find out whether we need to email a warning
         * to the webmaster.
         */

        $lockfile = fopen(Config::$log_file_lock, 'a+');
        if (!$lockfile) {
            error_log('FAIL: fopen failed in ' . __FUNCTION__ . ' in ' . __FILE__ . '');
            error_log(__FUNCTION__ . ' failed! Check server logs!');
            throw new MyRadioException('Failed to open log file.', 500);
        }
        $locked = flock($lockfile, LOCK_EX);
        if (!$locked) {
            error_log('FAIL: flock failed in ' . __FUNCTION__ . ' in ' . __FILE__ . '');
            error_log(__FUNCTION__ . ' failed! Check server logs!');
            throw new MyRadioException('Failed to open log lock file');
        }
        rewind($lockfile);

        // Run through the lockfile and grab the date/errfile pairs.
        $lockfile_data = [];
        while (!feof($lockfile)) {
            $buffer = fgets($lockfile);
            if ($buffer == '') {
                continue;  // EOF line is empty
            }
            $match = preg_match(
                '#^([0-9]{4}-[0-9]{2}-[0-9]{2}'
                .'T[0-9]{2}:[0-9]{2}:[0-9]{2}(?:\+|-)[0-9]{4})'
                .'\s+(.+)$#',
                $buffer,
                $matches
            );
            if (!$match) {
                error_log(
                    'FAIL: preg_match could not match '
                    . 'expected pattern in error log file, in '
                    . __FILE__ . ''
                );
                continue;
            }
            $lockfile_data[$matches[2]] = $matches[1];
        }

        // If an alert date exists for the current $errfile value,
        // then calculate whether or not the last error alert was
        // sent (by email) less than twenty-four hours ago. If so,
        // we don't need to send another alert right now.
        $need_to_email_alert = true; // starting assumption
        if (count($lockfile_data) > 0) {
            if (isset($lockfile_data[$errfile])) {
                $alert_date = date_create($lockfile_data[$errfile]);
                if (!$alert_date) {
                    error_log(
                        'FAIL: date_create could not create a date object'
                        . 'from the last alert date in ' . __FUNCTION__
                        . ' in ' .  __FILE__ . '.'
                    );
                    throw new MyRadioException('Failed to create date object.');
                }
                $alert_timestamp = date_format($alert_date, 'U');
                $current_timestamp = date('U');
                $diff_seconds = $current_timestamp - $alert_timestamp;
                // Change this to TESTING_ONLY to check that it works
                // but remember to change it back to One Day (or some
                // other value you deem suitable) when testing is
                // completed.
                if ($diff_seconds < 86400) {
                    // Last alert for this $errfile was less than
                    // twenty-four hours ago, so we don't need to
                    // send out another email alert. (Nor do we need
                    // to update the alert date in the lockfile.)
                    $need_to_email_alert = false;
                }
            }
        }

        /*
         * Stage three: send email and update lockfile, if necessary.
         */

        // Either we've never sent an alert about this page before,
        // or the last alert was sent more than 24 hours ago.
        // First, update the lockfile with the current date+time,
        // (so that lockfile can be released rather than hanging
        // around to wait for the email to be sent).
        if ($need_to_email_alert) {
            // Write out an updated version of the locking file after
            // updating the alert date for this $errfile entry.
            $lockfile_data[$errfile] = gmdate(DATE_ISO8601);
            ftruncate($lockfile, 0);  // we want to start from blank
            foreach ($lockfile_data as $page => $date) {
                fwrite($lockfile, $date . ' ' . $page . "\n");
            }
        }

        // Now that lockfile has been updated (if it was necessary)
        // it's time to release the lock, and close the file.
        if (flock($lockfile, LOCK_UN) == false
            || fclose($lockfile) == false
        ) {
            error_log('FAIL: flock or fclose failed in ' . __FUNCTION__ . ' in ' . __FILE__);
            error_log(__FUNCTION__ . ' failed! Check server logs!');
            throw new MyRadioException('Failed to release lock on log file.', 500);
        }

        // Now the lockfile has been released, send the alert email
        // if necessary.
        if ($need_to_email_alert) {
            if (Config::$error_report_email) {
                $rtnl = "</p>\r\n<p>";  // carriage return + newline
                ob_start();
                //debug_print_backtrace(); Sometimes these have passwords.
                $trace = str_replace("\n", $rtnl, ob_get_clean());
                $message = $errstr . $rtnl . $rtnl . $trace;
                if (class_exists('MyRadioEmail') && class_exists('Config')) {
                    $sent = MyRadioEmail::sendEmailToList(
                        MyRadio_List::getByName(Config::$error_report_email),
                        'MyRadio error alert',
                        $message
                    );
                    if (!$sent) {
                        error_log('FAIL: mail failed to send error alert email.');
                        // Good chance that if the mail command failed,
                        // then error_log will also fail to send mail,
                        // but we have to try.
                        error_log(__FUNCTION__ . ' failed! Check server logs!');
                        throw new MyRadioException('Failed to send email error alert.', 500);
                    }
                }
            }
        }
    }

    /**
     * Returns the number of errors encountered during execution.
     * @return int
     */
    public static function getErrorCount()
    {
        return self::$count;
    }

    public static function resetErrorCount()
    {
        self::$count = 0;
    }
}
