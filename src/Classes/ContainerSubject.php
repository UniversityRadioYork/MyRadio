<?php
/**
 * Provides the ContainerSubject trait for MyRadio
 * @package MyRadio_Core
 */

namespace MyRadio;

use \Pimple\Container;

/**
 * The ContainerSubject trait adds context of the service dependency container to an object.
 *
 * The object obviously needs to have metadata tables in the database for this
 * to work.
 *
 * @package MyRadio_Core
 */
abstract class ContainerSubject
{
	/**
     * Service injection mapper
     * 
     * @var \Pimple\Container
     */
    protected static $container;

    /**
     * Register service context container
     */
    public static function registerContainer(Container $container)
    {
        self::$container = $container;
    }
}
