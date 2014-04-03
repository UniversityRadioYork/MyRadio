<?php

/**
* Null session handler.
*
* @author Charles Pigott <lordaro@ury.org.uk>
 */
class MyRadioNullSession extends MyRadioSession
{
    public function __construct()
    {
        $this->db = null;
    }

    /**
     * Clear up old session entries in the database
     * This should be called automatically by PHP every one in a while
     */
    public function gc($lifetime)
    {
        return true;
    }

    /**
     * Reads the session data from the database. If no data exists, creates an
     * empty row
     */
    public function read($id)
    {
        return false;
    }

    /**
     * Writes changes to the session data to the database
     */
    public function write($id, $data)
    {
        return empty($data);
    }

    /**
     * Deletes the session entry from the database
     */
    public function destroy($id)
    {
        return !empty($id);
    }
}
