<?php
/**
 * This file provides the Database class for MyRadio.
 */
namespace MyRadio;

/**
 * This singleton class handles actual database connection.
 *
 * This is a Critical include!
 *
 * @depends Config
 */
class Database
{
    /**
     * Stores the singleton instance of the Database object.
     *
     * @var Database
     */
    private static $me;

    /**
     * Stores the resource id of the connection to the PostgreSQL database.
     *
     * @var resource
     */
    protected $db;

    /**
     * Stores the number of queries executed.
     *
     * @var int
     */
    private $counter = 0;

    /**
     * Rememebers if a transaction is in progress.
     *
     * @var bool
     */
    private $in_transaction = false;

    /**
     * Constructs the singleton database connector.
     */
    private function __construct()
    {
        $this->db = @pg_connect(
            'host='.Config::$db_hostname
            .' port=5432 dbname='.Config::$db_name
            .' user='.Config::$db_user
            .' password='.Config::$db_pass
        );
        if (!$this->db) {
            //Database isn't working. Throw an EVERYTHING IS BROKEN Exception
            throw new MyRadioException(
                'Database Connection Failed!',
                MyRadioException::FATAL
            );
        }
    }

    /**
     * Attempts to reset connection to the database server.
     */
    public function reconnect()
    {
        return pg_connection_reset($this->db);
    }

    /**
     * Check if the connection to the database server is still alive.
     *
     * @return bool Whether the connection is OK.
     */
    public function status()
    {
        return pg_connection_status($this->db) === PGSQL_CONNECTION_OK;
    }

    /**
     * Generic function that just runs a pg_query_params.
     *
     * @param string $sql      The query string to execute
     * @param array  $params   Parameters for the query
     * @param bool   $rollback Deprecated.
     *
     * @return resource A pg result reference
     *
     * @throws MyRadioException If the query fails
     * @assert ('SELECT * FROM public.tableethatreallydoesntexist') throws MyRadioException
     * @assert ('SELECT * FROM public.member') != false
     */
    public function query($sql, $params = [], $rollback = false)
    {
        if ($sql === 'BEGIN') {
            $this->in_transaction = true;
        } elseif ($sql === 'COMMIT' or $sql === 'ROLLBACK') {
            $this->in_transaction = false;
        }

        foreach ($params as $k => $v) {
            if (is_bool($v)) {
                $params[$k] = ($v ? 't' : 'f');
            }
            if (is_array($v) || is_object($v)) {
                throw new MyRadioException(
                    'Query failure: '.$sql.'<br>'
                    .'Params: '.var_export($params, true)
                    .'Tried to pass array to query<br>',
                    400
                );
            }
        }

        if (defined('DB_PROFILE')) {
            //Debug output
            echo $sql.' '.print_r($params, true).'...';
            $timer = microtime(true);
        }

        if (empty($params)) {
            pg_send_query($this->db, $sql);
        } else {
            pg_send_query_params($this->db, $sql, $params);
        }
        $result = pg_get_result($this->db);
        $errmsg = pg_result_error($result);
        if ($errmsg != "") {
            if ($this->in_transaction) {
                pg_query($this->db, 'ROLLBACK');
            }
            throw new MyRadioException(
                'Query failure: '.$sql.'<br>'
                .'Params: '.var_export($params, true).'<br>'
                .$errmsg,
                500
            );
        }
        ++$this->counter;

        if (defined('DB_PROFILE')) {
            echo(microtime(true) - $timer)."s\n";
        }

        return $result;
    }

    /**
     * Equates to a pg_num_rows($result).
     *
     * @param resource $result a reference to a postgres result set
     *
     * @return int The number of rose in the result set
     */
    public function numRows($result)
    {
        return pg_num_rows($result);
    }

    /**
     * The most commonly used database function
     * Equates to a pg_fetch_all(pg_query).
     *
     * @param string|resource $sql    The query string to execute or a psql result resource
     * @param array           $params Parameters for the query
     *
     * @return array An array of result rows (potentially empty)
     *
     * @throws MyRadioException
     */
    public function fetchAll($sql, $params = [])
    {
        if (is_resource($sql)) {
            return pg_fetch_all($sql);
        } elseif (is_string($sql)) {
            try {
                $result = $this->query($sql, $params);
            } catch (MyRadioException $e) {
                return [];
            }
            if (pg_num_rows($result) === 0) {
                return [];
            }

            return pg_fetch_all($result);
        } else {
            throw new MyRadioException('Invalid Request for $sql');
        }
    }

    /**
     * Equates to a pg_fetch_assoc(pg_query). Returns the first row.
     *
     * @param string $sql    The query string to execute or a psql result resource
     * @param array  $params Parameters for the query
     *
     * @return array The requested result row, or an empty array on failure
     *
     * @throws MyRadioException
     */
    public function fetchOne($sql, $params = [])
    {
        if (!is_resource($sql)) {
            try {
                $sql = $this->query($sql, $params);
            } catch (MyRadioException $e) {
                return [];
            }
        }

        return pg_fetch_assoc($sql);
    }

    /**
     * Equates to a pg_fetch_all_columns(pg_query,0). Returns all first column entries.
     *
     * @param string $sql      The query string to execute
     * @param array  $params   Paramaters for the query
     * @param bool   $rollback deprecated.
     *
     * @return array The requested result column, or an empty array on failure
     *
     * @throws MyRadioException
     */
    public function fetchColumn($sql, $params = [], $rollback = false)
    {
        try {
            $result = $this->query($sql, $params, $rollback);
        } catch (MyRadioException $e) {
            // TODO: temporary, uncomment this - marks.polakovs
            //return [];
            throw $e;
        }
        if (pg_num_rows($result) === 0) {
            return [];
        }

        return pg_fetch_all_columns($result, 0);
    }

    /**
     * Used to create the object, or return a reference to it if it already exists.
     *
     * @return Database One of these things
     */
    public static function getInstance()
    {
        if (!self::$me) {
            self::$me = new self();
        }

        return self::$me;
    }

    /**
     * Prevent copies being unintentionally made.
     *
     * @throws MyRadioException
     */
    public function __clone()
    {
        throw new MyRadioException('Attempted to clone a singleton');
    }

    public function intervalToTime($interval)
    {
        return strtotime('1970-01-01 '.$interval.'+00');
    }

    public function getCounter()
    {
        return $this->counter;
    }

    public function resetCounter()
    {
        $this->counter = 0;
    }

    public function clean($val)
    {
        return pg_escape_string($val);
    }

    public function getInTransaction()
    {
        return $this->in_transaction;
    }
}
