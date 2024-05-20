<?php

namespace MyRadio\MyRadio;

use MyRadio\Database;

/**
 * Custom session handler.
 */
class MyRadioSession implements \SessionHandlerInterface
{
    const TIMEOUT = 7200; //Session expires after 2hrs

    private $db;

    public static function factory()
    {
        if (isset($_SESSION)) {
            session_write_close();
        }

        return new static();
    }

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function open($save_path, $sesion_name): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    /**
     * Clear up old session entries in the database
     * This should be called automatically by PHP every one in a while.
     */
    public function gc($lifetime): int|false
    {
        $this->db->query(
            'DELETE FROM sso_session WHERE timestamp<$1',
            [CoreUtils::getTimestamp(time() - $lifetime)]
        );

        return true;
    }

    /**
     * Reads the session data from the database. If no data exists, creates an
     * empty row.
     */
    public function read($id): string|false
    {
        if (empty($id)) {
            return false;
        }

        // Use transaction to fix duplicate race condition on session storm.
        $this->db->query('BEGIN');
        $result = $this->db->fetchColumn(
            'SELECT data FROM sso_session
            WHERE id=$1 LIMIT 1',
            [$id]
        );
        if (empty($result)) {
            $this->db->query(
                'INSERT INTO sso_session (id, data, timestamp)
                VALUES ($1, \'\', NOW()) ON CONFLICT DO NOTHING',
                [$id]
            );
        }
        $this->db->query('COMMIT');

        if (empty($result)) {
            return '';
        } else {
            return $result[0];
        }
    }

    /**
     * Writes changes to the session data to the database.
     */
    public function write($id, $data): bool
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

        return $result !== false;
    }

    /**
     * Deletes the session entry from the database.
     */
    public function destroy($id): bool
    {
        if (empty($id)) {
            return false;
        }
        $this->db->query('DELETE FROM sso_session WHERE id=$1', [$id]);

        return true;
    }
}
