<?php

namespace MyRadio\Daemons;

use MyRadio\Config;
use MyRadio\Database;
use MyRadio\MyRadio\MyRadio_Daemon;
use MyRadio\ServiceAPI\MyRadio_Highlight;

class MyRadio_HighlightsDaemon extends MyRadio_Daemon
{
    public static function isEnabled()
    {
        return Config::$d_Highlights_enabled;
    }

    public static function run()
    {
        $runtimeKey = __CLASS__ . '_last_run';
        if (self::getVal($runtimeKey) > time() - (60 * 5)) {
            return;
        }

        try {
            self::generateHighlightLogs();
        } finally {
            self::setVal($runtimeKey, time());
        }
    }

    private static function generateHighlightLogs()
    {
        $db = Database::getInstance();
        $pending = $db->fetchColumn('SELECT highlight_id FROM schedule.highlight WHERE audio_log_requested = \'f\'');
        foreach ($pending as $id) {
            $hl = MyRadio_Highlight::getInstance($id);
            if ($hl->getEndTime() > (time() - 60*5)) {
                // Won't be ready yet
                continue;
            }
            $hl->requestAudioLog();
        }
    }

}