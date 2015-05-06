<?php
/**
 * Provides the DatabaseSubject trait for MyRadio
 * @package MyRadio_Core
 */

namespace MyRadio\Traits;

use \MyRadio\Database;

/**
 * The DatabaseSubject trait adds access to MyRadio Database information.
 *
 * It is intended for use along with a Dependency Injection framework in order
 * to use Setter Injection for all child classes.
 *
 * @package MyRadio_Core
 */
trait DatabaseSubject
{
    protected $db;

    public function setDatabase(Database $db)
    {
        $this->db = $db;
    }
}
