<?php

namespace MyRadio\Iface;

/**
 * A standard interface for cache systems. This should allow them to easily be
 * swapped out later (MemcachedProvider, APCProvider, PsqlProvider, FileProvider...)
 *
 * @package MyRadio_Core
 */
interface SessionProvider extends \ArrayAccess
{
    /**
     * Clear up old session entries in the database
     * This should be called automatically by PHP every one in a while
     */
    public function gc($lifetime);

    /**
     * Reads the session data from the database. If no data exists, creates an
     * empty row
     */
    public function read($id);

    /**
     * Writes changes to the session data to the database
     */
    public function write($id, $data);

    /**
     * Deletes the session entry from the database
     */
    public function destroy($id);
}
