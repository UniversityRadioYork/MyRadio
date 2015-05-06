<?php
/**
 * Provides the Configurable trait for MyRadio
 * @package MyRadio_Core
 */

namespace MyRadio\Traits;

use \MyRadio\Config;

/**
 * The Configurable trait adds access to MyRadio Configuration information.
 *
 * It is intended for use along with a Dependency Injection framework in order
 * to use Setter Injection for all child classes.
 *
 * @package MyRadio_Core
 */
trait Configurable
{
    protected $config;

    public function setConfig(Config $config)
    {
        $this->config = $config;
    }
}
