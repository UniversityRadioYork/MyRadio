<?php

namespace MyRadio\MyRadio;

use \MyRadio\Database;

/**
 * Custom session handler.
 *
 */
class MyRadioSession implements \ArrayAccess
{
    const TIMEOUT = 7200; //Session expires after 2hrs

    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function open($id)
    {
        return true;
    }

    public function close()
    {
        return true;
    }

    public function offsetSet($offset, $value)
    {
        if (defined('READONLY_SESSION') && READONLY_SESSION) {
            return true;
        }
        if (is_null($offset)) {
            $_SESSION[] = $value;
        } else {
            $_SESSION[$offset] = $value;
        }
    }

    public function offsetExists($offset)
    {
        return isset($_SESSION[$offset]);
    }

    public function offsetUnset($offset)
    {
        unset($_SESSION[$offset]);
    }

    public function offsetGet($offset)
    {
        return isset($_SESSION[$offset]) ? $_SESSION[$offset] : null;
    }

    /**
     * Clear up old session entries in the database
     * This should be called automatically by PHP every one in a while
     */
    public function gc($lifetime)
    {
        $this->db->query(
            'DELETE FROM sso_session WHERE timestamp<$1',
            [CoreUtils::getTimestamp(time() - $lifetime)]
        );

        return true;
    }

    /**
     * Reads the session data from the database. If no data exists, creates an
     * empty row
     */
    public function read($id)
    {
        if (empty($id)) {
            return false;
        }
        $result = $this->db->fetchColumn(
            'SELECT data FROM sso_session
            WHERE id=$1 LIMIT 1',
            [$id]
        );

        if (empty($result)) {
            $this->db->query(
                'INSERT INTO sso_session (id, data, timestamp)
                VALUES ($1, \'\', $2)',
                [$id, CoreUtils::getTimestamp()]
            );

            return '';
        }

        return $result[0];
    }

    /**
     * Writes changes to the session data to the database
     */
    public function write($id, $data)
    {
        if (empty($id)) {
            return false;
        }
        if (empty($data)) {
            return true;
        }
        $result = $this->db->query(
            'UPDATE sso_session SET data=$2, timestamp=NOW()
            WHERE id=$1',
            [$id, $data]
        );

        return ($result !== false);
    }

    /**
     * Deletes the session entry from the database
     */
    public function destroy($id)
    {
        if (empty($id)) {
            return false;
        }
        $this->db->query('DELETE FROM sso_session WHERE id=$1', [$id]);

        return true;
    }
}
