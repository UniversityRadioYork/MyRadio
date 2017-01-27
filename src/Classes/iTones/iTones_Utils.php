<?php

/**
 * This file provides the iTones_Utils class.
 */
namespace MyRadio\iTones;

use MyRadio\Config;
use MyRadio\MyRadioException;
use MyRadio\ServiceAPI\MyRadio_User;
use MyRadio\ServiceAPI\MyRadio_Track;
use MyRadio\iTones\iTones_TrackRequest;

/**
 * The iTones_Utils class provides generic utilities for controlling iTones - URY's Campus Jukebox.
 *
 * @uses    \Database
 */
class iTones_Utils extends \MyRadio\ServiceAPI\ServiceAPI
{
    private static $telnet_handle;
    private static $queues = ['requests', 'main'];
    private static $queue_cache = [];
    public static $ops = [];

    const REQUESTS_REMAINING_SQL = '
        SELECT
            GREATEST(0, ($1 - COUNT(trackid))) AS remaining
        FROM
            jukebox.request
        WHERE
            memberid = $2 AND
            (NOW() - date) < $3
        ;';

    /**
     * Gets the number of tracks the current user can currently request.
     *
     * @return int The number of tracks requestable as of now.
     */
    public static function getRemainingRequests()
    {
        return self::$db->fetchOne(self::REQUESTS_REMAINING_SQL, self::getRemainingRequestsParams())['remaining'];
    }

    /**
     * Creates the parameter list for a requests-remaining query.
     *
     * @return array The parameter list.
     */
    private static function getRemainingRequestsParams()
    {
        return [
            Config::$itones_request_maximum,
            MyRadio_User::getInstance()->getID(),
            Config::$itones_request_period,
        ];
    }

    /**
     * Push a track into the iTones request queue, if it hasn't been played
     * recently.
     *
     * @param MyRadio_Track $track
     * @param  $queue The jukebox_[x] queue to push to. Default requests.
     *                              "main" is the queue used for the main track scheduler, i.e. non-user entries.
     *
     * @return bool Whether the operation was successful
     */
    public static function requestTrack(MyRadio_Track $track, $queue = 'requests')
    {
        $track_request = new iTones_TrackRequest(
            $track,
            MyRadio_User::getInstance(),
            self::$db,
            $queue
        );

        return $track_request->request();
    }

    /**
     * Pushes the file at the given path to the iTones request queue.
     *
     * @param string $file Path to file on iTones server.
     *
     * @return bool Whether the operation was successful
     */
    public static function requestFile($file, $queue = 'requests')
    {
        self::verifyQueue($queue);
        $r = self::telnetOp('jukebox_'.$queue.'.push '.$file);

        return is_numeric($r);
    }

    /**
     * Returns Request IDs and Track IDs currently in the queue.
     *
     * @param string $queue Optional, as per definition in requestTrack()
     *
     * @return array 2D, such as [['requestid' => 1, 'trackid' => 72830, 'queue' => 'requests'], ...]
     */
    public static function getTracksInQueue($queue = 'requests')
    {
        self::verifyQueue($queue);
        if (isset(self::$queue_cache[$queue])) {
            return self::$queue_cache[$queue];
        }

        $info = explode(' ', self::telnetOp('jukebox_'.$queue.'.queue'));

        $items = [];
        foreach ($info as $item) {
            if (is_numeric($item)) {
                $meta = self::telnetOp('request.metadata '.$item);
                //Don't include items that are set to ignore
                if (stristr($meta, 'skip="true"') === false) {
                    //Get the trackid
                    $tid = preg_replace(
                        '/^.*filename=\"'.str_replace('/', '\\/', Config::$music_central_db_path)
                        .'\/records\/[0-9]+\/([0-9]+)\.mp3.*$/is',
                        '$1',
                        $meta
                    );
                    //Push the item
                    $items[] = ['requestid' => (int) $item, 'trackid' => (int) $tid, 'queue' => $queue];
                }
            }
        }

        self::$queue_cache[$queue] = $items;

        return $items;
    }

    /**
     * Returns all the tracks in all Queues.
     *
     * @return array compatible with getTracksInQueue
     */
    public static function getTracksInAllQueues()
    {
        $d = [];
        foreach (self::$queues as $queue) {
            $d = array_merge($d, self::getTracksInQueue($queue));
        }

        return $d;
    }

    /**
     * Check if a track is currently queued to be played in any queue.
     *
     * @return bool
     */
    public static function getIfQueued(MyRadio_Track $track)
    {
        foreach (self::$queues as $queue) {
            $r = self::getTracksInQueue($queue);
            foreach ($r as $req) {
                if ((int) $req['trackid'] === (int) $track->getID()) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Goes through the Queues, removing duplicate items.
     *
     * @return int The number of tracks that were removed from queues
     */
    public static function removeDuplicateItemsInQueues()
    {
        //Get the tracks in all the queues
        $tracks = [];
        foreach (self::$queues as $queue) {
            $tracks = array_merge($tracks, self::getTracksInQueue($queue));
        }

        //Go over each track, marking it as identified. If it's encountered a second time, kill it.
        $found = [];
        $removed = 0;
        foreach ($tracks as $track) {
            if (in_array($track['trackid'], $found)) {
                self::removeRequestFromQueue($track['queue'], $track['requestid']);
                ++$removed;
            } else {
                $found[] = $track['trackid'];
            }
        }

        return $removed;
    }

    /**
     * Empties all request queues.
     */
    public static function emptyQueues()
    {
        //Get the tracks in all the queues
        $tracks = [];
        foreach (self::$queues as $queue) {
            $tracks = array_merge($tracks, self::getTracksInQueue($queue));
        }

        foreach ($tracks as $track) {
            self::removeRequestFromQueue($track['queue'], $track['requestid']);
        }
    }

    /**
     * "Deletes" the given request from the given queue. It marks the item as
     * "ignore" but the rid remains in the queue.
     *
     * @param string $queue
     * @param int    $requestid
     */
    private static function removeRequestFromQueue($queue, $requestid)
    {
        self::verifyQueue($queue);

        self::telnetOp('jukebox_'.$queue.'.ignore '.$requestid);

        unset(self::$queue_cache[$queue]);
    }

    /**
     * Skips to the next track.
     *
     * @return string telnet response.
     */
    public static function skip()
    {
        return self::telnetOp('jukebox.skip');
    }

    private static function verifyQueue($queue)
    {
        if (in_array($queue, self::$queues) === false) {
            throw new MyRadioException('Invalid Queue!');
        }
    }

    /**
     * Runs a telnet command.
     *
     * @param string $command
     *
     * @return string
     */
    private static function telnetOp($command)
    {
        self::$ops[] = $command;
        if (empty(self::$telnet_handle)) {
            self::telnetStart();
        }

        fwrite(self::$telnet_handle, $command."\n");
        $response = '';
        $line = '';
        do {
            $response .= $line;
            $line = fgets(self::$telnet_handle, 1048576); //Read a max of 1MB of data
        } while (trim($line) !== 'END');

        //Remove the END
        return trim($response);
    }

    private static function telnetStart()
    {
        self::$telnet_handle = fsockopen(
            'tcp://'.Config::$itones_telnet_host,
            Config::$itones_telnet_port,
            $errno,
            $errstr,
            10
        );
        register_shutdown_function([__CLASS__, 'telnetEnd']);
    }

    public static function telnetEnd()
    {
        fwrite(self::$telnet_handle, "quit\n");
        fclose(self::$telnet_handle);
    }
}
