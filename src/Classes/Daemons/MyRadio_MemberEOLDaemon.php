<?php

namespace MyRadio\Daemons;

use MyRadio\Config;
use MyRadio\MyRadio\CoreUtils;
use MyRadio\MyRadioException;
use MyRadio\ServiceAPI\MyRadio_User;

/**
 * This Daemon carries out member end-of-life actions.
 */
class MyRadio_MemberEOLDaemon extends \MyRadio\MyRadio\MyRadio_Daemon
{
    public static function isEnabled()
    {
        return Config::$d_MemberEOL_enabled;
    }

    public static function run()
    {
        $hourkey = __CLASS__.'_last_run';
        if (self::getVal($hourkey) > time() - 60 * 60) {
            return;
        }

        $members = MyRadio_User::getPendingEOLMembers();
        foreach ($members as $member) {
            switch ($member->getEolState()) {
                case MyRadio_User::EOL_STATE_PENDING_DEACTIVATE:
                    $member->deactivate();
                    break;
                case MyRadio_User::EOL_STATE_PENDING_ARCHIVE:
                case MyRadio_User::EOL_STATE_PENDING_DELETE:
                    throw new MyRadioException('NYI');
            }
        }

        //Done
        self::setVal($hourkey, time());
    }
}
