<?php

namespace MyRadio\MyRadio;

class MyRadioDebug
{
    public static function isActive(): bool {
        return ($_REQUEST['myradio-debug'] ?? '') === 'true' || ($_SERVER['HTTP_X_MYRADIO_DEBUG'] ?? '') === 'true';
    }
}