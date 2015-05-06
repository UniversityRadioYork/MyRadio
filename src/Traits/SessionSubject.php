<?php
/**
 * Provides the SessionSubject trait for MyRadio
 * @package MyRadio_Core
 */

namespace MyRadio\Traits;

use \MyRadio\Iface\SessionProvider;

/**
 * The SessionSubject trait adds access to MyRadio Session information.
 *
 * It is intended for use along with a Dependency Injection framework in order
 * to use Setter Injection for all child classes.
 *
 * @package MyRadio_Core
 */
trait SessionSubject
{
    protected $session;

    public function setSession(SessionProvider $session)
    {
        $this->session = $session;
    }
}
