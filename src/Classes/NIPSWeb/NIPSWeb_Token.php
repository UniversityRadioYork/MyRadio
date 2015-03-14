<?php
/**
 * This file provides the NIPSWeb_Token class
 * @package MyRadio_NIPSWeb
 */

namespace MyRadio\NIPSWeb;

use \MyRadio\MyRadioException;

/**
 * The NIPSWeb_Token class
 *
 * @todo Implement Play Token support
 *
 * @package MyRadio_NIPSWeb
 * @uses    \Database
 */
class NIPSWeb_Token extends \MyRadio\ServiceAPI\ServiceAPI
{
    public static function createToken($trackid)
    {
        return true;
    }

    public static function hasToken($trackid)
    {
        return true;
    }

    public function getID()
    {
        return $this->id;
    }

    /**
     * Generate a unique session token - this is as Show Planner clients need to be more unique than a session id
     * in case the user has more than one instance of the planner open.
     * These IDs are also tied to the timeslot they were using at the time.
     * @return int a unique edit token
     */
    public static function getEditToken()
    {
        $r = self::$container['database']->fetchColumn(
            'INSERT INTO bapsplanner.client_ids (show_season_timeslot_id, session_id)
            VALUES ($1, $2) RETURNING client_id',
            [self::$container['session']['timeslotid'], session_id()]
        );

        if (empty($r)) {
            throw new MyRadioException('Failed to generate Show Planner edit token.', 500);
        }

        return (int) $r[0];
    }

    /**
     * Returns the Timeslot ID the edit token is assigned to
     * @param int $client_id
     * @return int
     */
    public static function getEditTokenTimeslot($client_id)
    {
        $r = self::$container['database']->fetchColumn(
            'SELECT show_season_timeslot_id FROM bapsplanner.client_ids
            WHERE client_id=$1 LIMIT 1',
            [$client_id]
        );

        return (int) $r[0];
    }
}
