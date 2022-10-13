<?php

namespace MyRadio\Daemons;

use MyRadio\Config;
use MyRadio\Database;
use MyRadio\Iface\CacheProvider;
use MyRadio\MyRadio\MyRadio_Daemon;
use MyRadio\ServiceAPI\MyRadio_Demo;
use MyRadio\ServiceAPI\MyRadio_Podcast;

/**
 * MyRadio_DemoCleanupDaemon cleans up cases where someone missed a training session, preventing them from signing
 * up for another one later.
 */
class MyRadio_DemoCleanupDaemon extends MyRadio_Daemon
{

    public static function isEnabled()
    {
        return Config::$d_DemoCleanup_enabled;
    }

    public static function run()
    {
        $runKey = __CLASS__ . '_last_run_hourly';
        if (self::getVal($runKey) > time() - 3600) {
            return;
        }

        try {
            self::cleanUp();
        } finally {
            // Done
            self::setVal($runKey, time());
        }
    }

    private static function cleanUp()
    {
        $db = Database::getInstance();
        $records = $db->fetchAll('
            SELECT da.demo_id, da.memberid
            FROM schedule.demo_attendee da
            INNER JOIN schedule.demo d on d.demo_id = da.demo_id
            LEFT JOIN public.member_presenterstatus mps ON mps.memberid = da.memberid AND mps.presenterstatusid = d.presenterstatusid
            WHERE d.demo_time < (NOW() - '1 hour'::interval)
            AND mps.memberpresenterstatusid IS NULL
        ');
        foreach ($records as $row) {
            $db->query('
                DELETE FROM schedule.demo_attendee
                WHERE demo_id = $1 AND memberid = $2
            ', [$row['demo_id'], $row['memberid']]);
            // No need to clear cache, as the "is already attending" check is done through SQL.
        }
    }
}

