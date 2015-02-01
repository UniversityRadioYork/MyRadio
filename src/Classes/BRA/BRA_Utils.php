<?php

/*
 * This file provides the BRA_Utils class for MyRadio
 * @package MyRadio_BRA
 */

namespace \MyRadio\Rapid;

/**
 * This class has helper functions for communicating with a BAPS Server over BRA
 *
 * @package MyRadio_BRA
 */
class BRA_Utils extends \MyRadio\ServiceAPI\ServiceAPI
{
    public static function getInstance($id = 0)
    {
        return new self();
    }

    public function __construct()
    {

    }

    public function getAllChannelInfo()
    {
        return json_decode(file_get_contents(Config::$bra_uri.'/channels'), true);
    }
}
