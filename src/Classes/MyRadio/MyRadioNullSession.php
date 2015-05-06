<?php

namespace MyRadio\MyRadio;

/**
* Null session handler.
*
 */
class MyRadioNullSession implements \MyRadio\Iface\SessionProvider
{
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

    public function offsetSet($offset, $value)
    {
        return true;
    }

    public function offsetExists($offset)
    {
        return false;
    }

    public function offsetUnset($offset)
    {
        return true;
    }

    public function offsetGet($offset)
    {
        return null;
    }
}
