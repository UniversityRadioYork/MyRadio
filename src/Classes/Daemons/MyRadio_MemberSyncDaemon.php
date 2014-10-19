<?php

namespace MyRadio\Daemons;

use \MyRadio\Config;
use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\ServiceAPI\MyRadio_User;

/**
 * This Daemon creates new Member accounts based on data from the YUSU API
 */
class MyRadio_MemberSyncDaemon extends \MyRadio\MyRadio\MyRadio_Daemon
{
    public static function isEnabled()
    {
        return Config::$d_MemberSync_enabled;
    }

    public static function run()
    {
        $hourkey = __CLASS__.'_last_run';
        if (self::getVal($hourkey) > time() - 300) {
            return;
        }

        $members = CoreUtils::callYUSU('ListMembers');

        foreach ($members as $member) {
            dlog('Checking YUSU Member '.$member['EmailAddress'], 4);
            $result = MyRadio_User::findByEmail($member['EmailAddress']);

            if (empty($result)) {
                dlog('Member '.$member['EmailAddress'].' does not exist.', 3);
            } elseif ($member['Paid'] != null) {
                dlog('Member '.$member['EmailAddress'].' matches '.$result->getID().'.', 4);
                dlog('Setting '.$result->getID().' payment to '.Config::$membership_fee.'.', 3);
                $result->setPayment(Config::$membership_fee);
            }
        }

        //Done
        self::setVal($hourkey, time());
    }
}
