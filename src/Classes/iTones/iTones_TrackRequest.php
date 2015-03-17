<?php

/**
 * This file provides the iTones_TrackRequest class
 * @package MyRadio_iTones
 */

namespace MyRadio\iTones;

use \MyRadio\Config;
use \MyRadio\Database;
use \MyRadio\ServiceAPI\MyRadio_User;
use \MyRadio\ServiceAPI\MyRadio_Track;
use \MyRadio\ServiceAPI\MyRadio_TracklistItem;

/**
 * Method object for requesting a track be played by iTones.
 *
 * @package MyRadio_iTones
 * @uses    \Database
 */
class iTones_TrackRequest
{
    const CAN_MAKE_REQUEST_SQL = '
        SELECT
            (COUNT(trackid) <= $1) AS allowed
        FROM
            jukebox.request
        WHERE
            memberid = $2 AND
            (NOW() - date) < $3
        ;';

    const LOG_REQUEST_SQL = '
        INSERT INTO
            jukebox.request(trackid, memberid, queue, date)
        VALUES
            ($1, $2, $3, NOW())
        ;';

    /**
     * Constructs a track request.
     *
     * @param MyRadio_Track $track     The track being requested.
     * @param MyRadio_User  $requester The user performing the request.
     * @param Database      $database  The database to query for request data.
     * @param String        $queue     The iTones queue to request into.
     */
    public function __construct(
        MyRadio_Track $track,
        MyRadio_User  $requester,
        Database      $database,
        $queue = 'requests'
    ) {
        $this->track     = $track;
        $this->requester = $requester;
        $this->database  = $database;
        $this->queue     = $queue;
    }

    /**
     * Performs the track request.
     *
     * @return bool Whether the operation was successful
     */
    public function request()
    {
        $success = false;

        if ($this->canRequestTrack() === true) {
            $success = $this->requestTrackAndLog();
        }

        return $success;
    }

    /**
     * Checks whether the given track can be requested by the current user.
     *
     * @return bool Whether the track can be requested.
     */
    private function canRequestTrack()
    {
        return (
            $this->trackCanBePlayed() &&
            $this->userCanMakeRequests()
        );
    }

    /**
     * Checks whether the given track can be played at the moment.
     *
     * This generally means playing it won't trip licencing quotae.
     *
     * @return bool Whether the track can be played.
     */
    private function trackCanBePlayed()
    {
        return !(MyRadio_TracklistItem::getIfPlayedRecently($this->track));
    }

    /**
     * Checks whether the current user can make requests at the moment.
     *
     * This generally means requesting won't trip the user's request quota.
     *
     * @return bool Whether the current user can make a request.
     */
    public function userCanMakeRequests()
    {
        return $this->areRequestsAllowedBy($this->userCanMakeRequestsQuery());
    }

    /**
     * Checks to see if the database said the current user can make requests.
     *
     * @param object $results The results from a can-make-requests query.
     *
     * @return bool Whether the current user can make a request.
     */
    private function areRequestsAllowedBy($results)
    {
        return $results['allowed'] == 't';
    }

    /**
     * Runs a query to see if the current user can make requests at the oment.
     *
     * @return object The database query results.
     */
    private function userCanMakeRequestsQuery()
    {
        return $this->database->fetchOne(
            self::CAN_MAKE_REQUEST_SQL,
            $this->userCanMakeRequestsParams()
        );
    }

    /**
     * Creates the parameter list for a can-make-requests query.
     *
     * @return array The parameter list.
     */
    private function userCanMakeRequestsParams()
    {
        return [
            self::$container['config']->itones_request_maximum,
            $this->requester->getID(),
            self::$container['config']->itones_request_period
        ];
    }

    /**
     * Requests a file, and logs the request if successful.
     *
     * @return bool Whether the request was successful.
     */
    private function requestTrackAndLog()
    {
        $success = iTones_Utils::requestFile(
            $this->track->getPath(),
            $this->queue
        );

        if ($success) {
            $this->logRequest();
        }

        return $success;
    }

    /**
     * Logs that the current user has made a request.
     *
     * @param MyRadio_Track $track The track to log in the database.
     *
     * @return null Nothing.
     */
    private function logRequest()
    {
        $this->database->query(
            self::LOG_REQUEST_SQL,
            $this->logRequestParams()
        );
    }

    /**
     * Creates the parameter list for a log-request query.
     *
     * @param MyRadio_Track $track The track to log in the database.
     *
     * @return array The parameters array.
     */
    private function logRequestParams()
    {
        return [
            $this->track->getID(),
            $this->requester->getID(),
            $this->queue
        ];
    }
}
