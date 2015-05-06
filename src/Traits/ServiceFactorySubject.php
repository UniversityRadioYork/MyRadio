<?php
/**
 * Provides the ServiceFactorySubject trait for MyRadio
 * @package MyRadio_Core
 */

namespace MyRadio\Traits;

use \MyRadio\Iface\ServiceFactory;

/**
 * The ServiceFactorySubject trait adds access to MyRadio ServiceAPIs.
 *
 * It is intended for use along with a Dependency Injection framework in order
 * to use Setter Injection for all child classes.
 *
 * @package MyRadio_Core
 */
trait ServiceFactorySubject
{
    protected $factory;

    public function setServiceFactory(ServiceFactory $factory)
    {
        $this->factory = $factory;
    }
}
