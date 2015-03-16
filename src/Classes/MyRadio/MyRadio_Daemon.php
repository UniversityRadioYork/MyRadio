<?php

namespace MyRadio\MyRadio;

use \MyRadio\MyRadioException;

abstract class MyRadio_Daemon
{
    public static function isEnabled($config)
    {
        return false;
    }

    public static function run();

    protected static function getVal($container, $key)
    {
        $data = json_decode(file_get_contents($container['config']->daemon_lock_file), true);

        return (isset($data[$key])) ? $data[$key] : null;
    }

    protected static function setVal($container, $key, $value)
    {
        $data = @json_decode(file_get_contents($container['config']->daemon_lock_file), true);
        $data[$key] = $value;

        file_put_contents($container['config']->daemon_lock_file, json_encode($data));
    }
}
