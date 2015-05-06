<?php
namespace MyRadio\Iface;

/**
 * Provides a standard way of getting an object that can be easily mocked for testing.
 *
 * @package MyRadio_Core
 */
interface ServiceFactory
{
    public function getInstanceOf($class, $id = null);
}
