<?php

namespace MyRadio\Iface;

/**
 * A standard interface for something that enables operating on a MyRadio API.
 */
interface APICaller
{
    /**
     * Tells you whether this APICaller can call the given call.
     *
     * @param string $class  The class name that is being requested (e.g. \MyRadio\ServiceAPI\MyRadio_User)
     * @param string $method the method name that is being requested (e.g. getOfficerships)
     *
     * @return bool Whether or not the user can call this.
     */
    public function canCall($class, $method);

    /**
     * Log that this APICaller has Called an API Call.
     */
    public function logCall($uri, $args);

    /**
     * Tells you whether this APICaller can use the given mixins.
     *
     * @param string   $class  The class the method belongs to (actual, not API Alias)
     * @param string[] $mixins The mixins being called
     *
     * @return bool Whether or not the user can call this
     */
    public function canMixin($class, $mixins);
}
