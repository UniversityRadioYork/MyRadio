<?php

namespace MyRadio;

use GeoIp2\Database\Reader;

class MyRadioGeoIP
{
    private static ?Reader $reader = null;

    public static function wakeup()
    {
        if (self::$reader === null) {
            if (Config::$geoip_database_path === '') {
                throw new MyRadioException(
                    'GeoIP database path not configured!'
                );
            }
            self::$reader = new Reader(config::$geoip_database_path);
        }
    }

    /**
     * @return Reader
     */
    public static function getInstance()
    {
        if (self::$reader === null) {
            throw new MyRadioException(
                'GeoIP not initialised'
            );
        }
        return self::$reader;
    }
}
