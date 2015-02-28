<?php
/**
 * Provides the MyRadio_Type class for MyRadio
 * @package MyRadio_Core
 */

namespace MyRadio\ServiceAPI;

/**
 * The Type abstract class implements the URY database 'type' pattern, wherein
 * items describing types/categories of other items have a machine-readable
 * name and human-readable description.
 *
 * @package MyRadio_Core
 * @uses    \Database
 */
abstract class MyRadio_Type extends ServiceAPI
{
    /**
     * The machine-readable name of the type.
     * @var String
     */
    private $name;

    /**
     * The human-readable description/descriptive name of the type.
     * @var String
     */
    private $description;

    /**
     * This should be included in the implementor's __construct function.
     *
     * @param $name         The machine-readable name of the type.
     * @param $description  The human-readable description/descriptive name of
     *                      the type.
     */
    protected function constructType($name, $description)
    {
        $this->name = $name;
        $this->description = $description;
    }

    /**
     * Retrieves this type's machine readable name.
     *
     * @return The name.
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Retrieves this type's human readable description.
     *
     * @return The description.
     */
    public function getDescription()
    {
        return $this->description;
    }
}
