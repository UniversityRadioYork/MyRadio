<?php
namespace MyRadio\MyRadio;

/**
 * Custom session handler.
 *
 */
abstract class MyRadioAbstractSession implements \MyRadio\Iface\SessionProvider
{
    public function __construct()
    {

        //Override any existing session
        if (isset($_SESSION)) {
            session_write_close();
            session_id($_COOKIE['myradiosession']);
        }

        session_set_save_handler(
            [$this, 'open'],
            [$this, 'close'],
            [$this, 'read'],
            [$this, 'write'],
            [$this, 'destroy'],
            [$this, 'gc']
        );
        session_start();
    }
}
