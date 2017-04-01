<?php

/**
 * This file provides the CoreUtils class for MyRadio.
 */
namespace MyRadio\MyRadio;

use MyRadio\Config;
use MyRadio\Database;
use MyRadio\MyRadioTwig;
use MyRadio\MyRadioException;
use MyRadio\MyRadioError;
use MyRadio\Iface\MyRadio_DataSource;

/**
 * Standard API Utilities. Basically miscellaneous functions for the core system
 * No database accessing etc should be setup here.
 */
class CoreUtils
{
    /**
     * Stores the result of CoreUtils::getAcademicYear.
     *
     * This cut 8k queries off of loading one test page...
     *
     * @var int
     */
    private static $academicYear;

    /**
     * Stores module name => id mappings to reduce query load - they are initialised once and stored.
     *
     * @var array
     */
    private static $module_ids = [];

    /**
     * Stores action name => id mappings to reduce query load - they are initialised once and stored.
     *
     * @var array
     */
    private static $action_ids = [];

    /**
     * Checks whether a given Module/Action combination is valid.
     *
     * @param string $module The module to check
     * @param string $action The action to check. Default 'default'
     *
     * @return bool Whether or not the request is valid
     * @assert ('Core', 'default') === true
     * @assert ('foo', 'barthatdoesnotandwillnoteverexisteverbecauseitwouldbesilly') === false
     * @assert ('../foo', 'bar') === false
     * @assert ('foo', '../bar') === false
     */
    public static function isValidController($module, $action = null)
    {
        if ($action === null) {
            $action = Config::$default_action;
        }
        try {
            self::actionSafe($action);
            self::actionSafe($module);
        } catch (MyRadioException $e) {
            return false;
        }

        /* This is better than file_exists because it ensures that the response
         * is valid for a version which has the file when live does not */
        return is_string(stream_resolve_include_path('Controllers/'.$module.'/'.$action.'.php'));
    }

    /**
     * Provides a template engine object compliant with TemplateEngine interface.
     *
     * @return MyRadioTwig
     *
     * @todo Make this generalisable for drop-in template engine replacements
     * @assert () !== false
     * @assert () !== null
     */
    public static function getTemplateObject()
    {
        require_once 'vendor/twig/twig/lib/Twig/Autoloader.php';
        \Twig_Autoloader::register();

        return new MyRadioTwig();
    }

    /**
     * Checks whether a requested action is safe.
     *
     * @param string $action A module action
     *
     * @return bool Whether the module is safe to be used on a filesystem
     *
     * @throws MyRadioException Thrown if directory traversal detected
     * @assert ('safe!') === true
     * @assert ('../notsafe!') throws MyRadioException
     */
    public static function actionSafe($action)
    {
        if (strpos($action, '/') !== false) {
            //Someone is trying to traverse directories
            throw new MyRadioException('Directory Traversal Thrwated');
        }

        return true;
    }

    /**
     * Formats pretty much anything into a happy, human readable date/time.
     *
     * @param string $timestring Some form of time
     * @param bool   $time       Whether to include Hours,Mins. Default yes
     *
     * @return string A happy time
     * @assert (40000) == '01/01/1970'
     */
    public static function happyTime($timestring, $time = true, $date = true)
    {
        return date(
            ($date ? 'd/m/Y' : '').($time && $date ? ' ' : '').($time ? 'H:i' : ''),
            is_numeric($timestring) ? $timestring : strtotime($timestring)
        );
    }

    /**
     * Formats a number into h:m:s format.
     *
     * @param int $int
     *
     * @return string
     */
    public static function intToTime($int)
    {
        $hours = floor($int / 3600);
        if ($hours === 0) {
            $hours = null;
        } else {
            $hours = $hours.':';
        }

        $mins = floor(($int - ($hours * 3600)) / 60);
        $secs = ($int - ($hours * 3600) - ($mins * 60));

        return "$hours$mins:$secs";
    }

    /**
     * Returns a postgresql-formatted timestamp.
     *
     * @param int $time The time to get the timestamp for. Default right now.
     *
     * @return string a timestamp
     * @assert (30) == '1970-01-01 00:00:30'
     */
    public static function getTimestamp($time = null)
    {
        if ($time === null) {
            $time = time();
        }

        return gmdate('Y-m-d H:i:s+00', $time);
    }

    /**
     * Returns the ISO8601 Year and Week Number for the given time.
     *
     * @param int $time The time to get the info for, default now.
     *
     * @return array [year, week_number]
     */
    public static function getYearAndWeekNo($time = null)
    {
        if ($time === null) {
            $time = time();
        }

        $year_absolute = (int) gmdate('Y', $time);
        $week_number = (int) gmdate('W', $time);
        $month = (int) gmdate('n', $time);

        if ($month === 1 && $week_number > 50) {
            //This is the final week of *last* year
            $year_adjusted = $year_absolute - 1;
        } else {
            $year_adjusted = $year_absolute;
        }

        return [$year_adjusted, $week_number];
    }

    /**
     * Gives you the starting year of the current academic year.
     *
     * @return int year
     * @assert () == 2013
     */
    public static function getAcademicYear()
    {
        if (empty(self::$academicYear)) {
            $term = Database::getInstance()->fetchColumn(
                'SELECT start FROM public.terms WHERE descr=\'Autumn\'
                AND EXTRACT(year FROM start) = $1',
                [date('Y')]
            );

            // Default to this year
            $account_reset_time = strtotime('+'.Config::$account_expiry_before.' days');
            if (empty($term) || strtotime($term[0]) <= $account_reset_time) {
                self::$academicYear = date('Y');
            } else {
                self::$academicYear = date('Y') - 1;
            }
        }

        return self::$academicYear;
    }

    /**
     * Returns a postgresql formatted interval.
     *
     * @param int $start The start time
     * @param int $end   The end time
     *
     * @return string a PgSQL valid interval value
     * @assert (0, 0) == '0 seconds'
     */
    public static function makeInterval($start, $end)
    {
        return $end - $start.' seconds';
    }

    /**
     * Runs the relevant encode commands on an uploaded music file.
     *
     * @param string $tmpfile The original unencoded filepath
     * @param string $dbfile  The destination filepath, sans extension
     * @throws MyRadioException Thrown if encode or move commands appear to fail.
     * @note Similar command is run for podcast uploads, which are done slightly differently
     */
    public static function encodeTrack($tmpfile, $dbfile)
    {
        $commands = [
            'mp3' => "nice -n 15 ffmpeg -i '{$tmpfile}' -ab 192k -f mp3 -map 0:a '{$dbfile}.mp3'",
            'ogg' => "nice -n 15 ffmpeg -i '{$tmpfile}' -acodec libvorbis -ab 192k -map 0:a '{$dbfile}.ogg'"
        ];
        $escaped_commands = array_map('escapeshellcmd', $commands);
        $failed_formats = [];

        foreach ($escaped_commands as $format => $command) {
            exec($command, $command_stdout, $command_exit_code);
            if ($command_exit_code) {
                $failed_formats[] = $format;
            }
        }

        if ($failed_formats) {
            throw new MyRadioException('Conversion failed: ' . implode(',', $failed_formats), 500);
        } elseif (!file_exists($dbfile.'.mp3') || !file_exists($dbfile.'.ogg')) {
            throw new MyRadioException('Conversion failed', 500);
        }

        rename($tmpfile, "{$dbfile}.mp3.orig");
        if (!file_exists("{$dbfile}.mp3.orig")) {
            throw new MyRadioException('Could not move file to library.');
        }
    }

    /**
     * A simple debug method that only displays output for a specific user.
     *
     * @param int    $userid  The ID of the user to display for
     * @param string $message The HTML to display for this user
     * @assert (7449, 'Test') == null
     */
    public static function debugFor($userid, $message)
    {
        if ($_SESSION['memberid'] == $userid) {
            echo '<p>'.$message.'</p>';
        }
    }

    /**
     * Returns the ID of a Module, creating it if necessary.
     *
     * This method first caches all module IDs, if they aren't already available. It then checks
     * if the given module exists, and if not it creates one, generating an ID.
     *
     * @param string $module
     *
     * @return int
     */
    public static function getModuleId($module)
    {
        if (empty(self::$module_ids)) {
            $result = Database::getInstance()->fetchAll('SELECT name, moduleid FROM myury.modules');
            foreach ($result as $row) {
                self::$module_ids[$row['name']] = $row['moduleid'];
            }
        }

        if (empty(self::$module_ids[$module])) {
            //The module needs creating
            $result = Database::getInstance()->fetchColumn(
                'INSERT INTO myury.modules (serviceid, name)
                VALUES ($1, $2) RETURNING moduleid',
                [Config::$service_id, $module]
            );
            if ($result) {
                self::$module_ids[$module] = $result[0];
            } else {
                return;
            }
        }

        return self::$module_ids[$module];
    }

    /**
     * Returns the ID of a Service/Module/Action request, creating it if necessary.
     *
     * @param int    $module
     * @param string $action
     *
     * @return int
     */
    public static function getActionId($module, $action)
    {
        if (empty(self::$action_ids)) {
            $result = Database::getInstance()->fetchAll('SELECT name, moduleid, actionid FROM myury.actions');
            foreach ($result as $row) {
                self::$action_ids[$row['name'].'-'.$row['moduleid']] = $row['actionid'];
            }
        }

        if (empty(self::$action_ids[$action.'-'.$module])) {
            //The action needs creating
            $result = Database::getInstance()->fetchColumn(
                'INSERT INTO myury.actions (moduleid, name)
                VALUES ($1, $2) RETURNING actionid',
                [$module, $action]
            );
            if ($result) {
                self::$action_ids[$action.'-'.$module] = $result[0];
            } else {
                return;
            }
        }

        return self::$action_ids[$action.'-'.$module];
    }

    /**
     * Parses an object or array into client array datasource.
     *
     * @param mixed $data
     *
     * @return array
     */
    public static function dataSourceParser($data, $mixins = [])
    {
        if (is_object($data) && $data instanceof MyRadio_DataSource) {
            return $data->toDataSource($mixins);
        } elseif (is_array($data)) {
            foreach ($data as $k => $v) {
                $data[$k] = self::dataSourceParser($v, $mixins);
            }

            return $data;
        } else {
            return $data;
        }
    }

    //from http://www.php.net/manual/en/function.xml-parse-into-struct.php#109032
    public static function xml2array($xml)
    {
        $opened = [];
        $opened[1] = 0;
        $xml_parser = xml_parser_create();
        xml_parse_into_struct($xml_parser, $xml, $xmlarray);
        $array = array_shift($xmlarray);
        unset($array['level']);
        unset($array['type']);
        $arrsize = sizeof($xmlarray);
        for ($j = 0; $j < $arrsize; ++$j) {
            $val = $xmlarray[$j];
            switch ($val['type']) {
                case 'open':
                    $opened[$val['level']] = 0;
                    /* Fall through */
                case 'complete':
                    $index = '';
                    for ($i = 1; $i < ($val['level']); ++$i) {
                        $index .= '['.$opened[$i].']';
                    }
                    $path = explode('][', substr($index, 1, -1));
                    $value = &$array;
                    foreach ($path as $segment) {
                        $value = &$value[$segment];
                    }
                    $value = $val;
                    unset($value['level']);
                    unset($value['type']);
                    if ($val['type'] == 'complete') {
                        ++$opened[$val['level'] - 1];
                    }
                    break;
                case 'close':
                    $opened[$val['level'] - 1]++;
                    unset($opened[$val['level']]);
                    break;
            }
        }

        return $array;
    }

    public static function requireTimeslot()
    {
        if (!isset($_SESSION['timeslotid'])) {
            URLUtils::redirect('MyRadio', 'timeslot', ['next' => $_SERVER['REQUEST_URI']]);
            exit;
        }
    }

    /**
     * Returns a randomly selected item from the list, in a biased manner
     * Weighted should be an integer - how many times to put the item into the bag.
     *
     * @param array $data 2D of Format [['item' => mixed, 'weight' => n], ...]
     */
    public static function biasedRandom($data)
    {
        $bag = [];

        foreach ($data as $ball) {
            for (; $ball['weight'] > 0; --$ball['weight']) {
                $bag[] = $ball['item'];
            }
        }

        return $bag[array_rand($bag)];
    }

    //Reports some things
    public static function shutdown()
    {
        session_write_close(); //It doesn't seem to do this itself sometimes.
        try {
            $db = Database::getInstance();
        } catch (MyRadioException $e) {
            return;
        }
        if (!empty($_SERVER['SERVER_ADDR'])) {
            //Don't let the client wait for us
            flush();
        }

        //Discard any in-progress transactions
        if ($db->getInTransaction()) {
            $db->query('ROLLBACK');
        }
    }

    /**
     * Ring YUSU's API and ask how it's doing.
     *
     * Currently, ListMembers is the only function available. Dan Bishop has plans for more at a later date.
     *
     * @return array JSON Response, forced to assoc array
     */
    public static function callYUSU($function = 'ListMembers')
    {
        $options = [
            'http' => [
                'method' => 'GET',
                'header' => "User-Agent: MyRadio\r\n",
            ],
        ];
        $context = stream_context_create($options);

        return json_decode(
            file_get_contents(
                Config::$yusu_api_website
                .'?apikey='
                .Config::$yusu_api_key
                .'&function='
                .$function,
                false,
                $context
            ),
            true
        );
    }

    public static function getSafeHTML($dirty_html)
    {
        $config = \HTMLPurifier_Config::createDefault();
        $purifier = new \HTMLPurifier($config);

        return $purifier->purify($dirty_html);
    }

    /**
     * Returns lookup values for Status for a select box.
     *
     * @return array
     */
    public static function getStatusLookup()
    {
        return Database::getInstance()->fetchAll(
            'SELECT statusid AS value, descr AS text FROM public.l_status
            ORDER BY descr ASC'
        );
    }

    /**
     * Returns information about the $_REQUEST array.
     *
     * This *MUST* be used instead of print_r($_REQUEST) or var_dump($_REQUEST)
     * in debug output.
     *
     * @return string var_dump output
     */
    public static function getRequestInfo()
    {
        ob_start();
        if (isset($_REQUEST['redact']) || isset($_REQUEST['pass']) || isset($_REQUEST['password'])) {
            $info = [];
            foreach ($_REQUEST as $k => $v) {
                if (!in_array($k, $_REQUEST['redact']) && $k !== 'pass' && $k !== 'password') {
                    $info[$k] = $v;
                } else {
                    $info[$k] = '**REDACTED**';
                }
            }
            var_dump($info);
        } else {
            var_dump($_REQUEST);
        }

        return ob_get_clean();
    }

    /**
     * Returns information about the $_SERVER array.
     *
     * @return string var_dump output
     */
    public static function getServerInfo()
    {
        ob_start();
        var_dump($_SERVER);
        return ob_get_clean();
    }

    /**
     * Generates a completely pseudorandom string, aimed for Salt purposes.
     *
     * @param int $pwdLen The length of the string to generate
     *
     * @return string a random string of length $pwdLen
     */
    public static function randomString($pwdLen = 8)
    {
        $result = '';
        $pwdSource = '1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        srand((double) microtime() * 1000000);
        while ($pwdLen) {
            $result .= substr($pwdSource, rand(0, strlen($pwdSource) - 1), 1);
            --$pwdLen;
        }

        return $result;
    }

    /**
     * I'm becoming a Python person who just expects this to be a thing.
     *
     * Copypasta from http://stackoverflow.com/questions/834303/startswith-and-endswith-functions-in-php
     */
    public static function startsWith($haystack, $needle)
    {
        // search backwards starting from haystack length characters from the end
        return $needle === '' || strrpos($haystack, $needle, -strlen($haystack)) !== false;
    }

    private function __construct()
    {
    }

    /**
     * Generates a new password consisting of two words and a two-digit number.
     *
     * @todo Make this crypto secure random?
     *
     * @return string
     */
    public static function newPassword()
    {
        return self::$words[array_rand(self::$words)].rand(10, 99)
            .self::$words[array_rand(self::$words)];
    }

    /**
     * Words used by CoreUtils::newPassword.
     *
     * @var string[]
     */
    private static $words = [
        'Radio',
        'Microphone',
        'Studio',
        'Speaker',
        'Headphone',
        'Compressor',
        'Fader',
        'Schedule',
        'Podcast',
        'Music',
        'Track',
        'Record',
        'Artist',
        'Publisher',
        'Album',
        'Broadcast',
        'Transmitter',
        'Silence',
        'Selector',
        'Management',
        'Engineering',
        'Computing',
        'Business',
        'Events',
        'Speech',
        'Training',
        'Presenting',
        'Stores',
        'Tardis',
        'Relay',
        'Jingle',
        'Advert',
        'Frequency',
        'Modulation',
        'Vinyl',
        'Broadcasting',
    ];
}
