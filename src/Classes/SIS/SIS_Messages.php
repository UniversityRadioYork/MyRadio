<?php

/*
 * This file provides the SIS_Utils class for MyRadio
 * @package MyRadio_SIS
 */

namespace MyRadio\SIS;

use MyRadio\ServiceAPI\ServiceAPI;
use MyRadio\ServiceAPI\MyRadio_Timeslot;

/**
 * This class has helper functions for building SIS.
 */
class SIS_Messages extends ServiceAPI
{
    const MSG_STATUS_UNREAD = 1;
    const MSG_STATUS_READ = 2;
    const MSG_STATUS_DELETED = 3;
    const MSG_STATUS_JUNK = 4;
    const MSG_STATUS_ABUSIVE = 5;

    /**
     * Returns an array of messages.
     *
     * @param int $timeslotid What timeslot to fetch messages for
     * @param int $offset     Only message IDs greater than this will be returned
     *
     * @return array An array of SIS messages
     */
    public static function getMessages($timeslotid, $offset = 0)
    {
        return MyRadio_Timeslot::getInstance($timeslotid)->getMessages($offset);
    }

    /**
     * Update the status of a message.
     *
     * @param int $id     The ID of the message to update
     * @param int $status The new status of the message
     */
    public static function setMessageStatus($id, $status = self::MSG_STATUS_READ)
    {
        self::$db->query(
            'UPDATE sis2.messages SET statusid=$1 WHERE commid=$2',
            [$status, $id]
        );
    }
}
