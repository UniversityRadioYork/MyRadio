<?php

/**
 * Provides the MyRadio_Selector class for MyRadio.
 */
namespace MyRadio\ServiceAPI;

use MyRadio\Config;
use MyRadio\Database;
use MyRadio\MyRadioException;
use MyRadio\MyRadio\CoreUtils;
use MyRadio\iTones\iTones_Utils;

/**
 * The Selector class provies an abstractor to the `sel` service
 * and the Selector logs.
 *
 * BE CAREFUL USING SET METHOD IN THIS CLASS.
 * THEY *WILL* CHANGE THE STATION OUTPUT.
 *
 * @uses    \Database
 */
class MyRadio_Selector
{
    /**
     * The current studio is Studio 1.
     */
    const SEL_STUDIO1 = 1;

    /**
     * The current studio is Studio 2.
     */
    const SEL_STUDIO2 = 2;

    /**
     * The current studio is Jukebox.
     */
    const SEL_JUKEBOX = 3;

    /**
     * The current studio is Outside Broadcast.
     */
    const SEL_OB = 4;

    /**
     * The studio selection was made by the Selector Telnet interface.
     */
    const FROM_AUX = 0;

    /**
     * The studio selection was made by Studio 1.
     */
    const FROM_S1 = 1;

    /**
     * The studio selection was made by Studio 2.
     */
    const FROM_S2 = 2;

    /**
     * The studio selection was made on the main selector panel in the hub.
     */
    const FROM_HUB = 3;

    /**
     * The selector is unlocked.
     */
    const LOCK_NONE = 0;

    /**
     * The selector has been locked remotely. The physical selector buttons
     * have no effect. This API still responds.
     */
    const LOCK_AUX = 1;

    /**
     * The selector has been locked physically. The physical selector buttons
     * and this API do not respond.
     */
    const LOCK_KEY = 2;

    /**
     * No studios are on.
     */
    const ON_NONE = 0;

    /**
     * Studio 1 is switched on.
     */
    const ON_S1 = 1;

    /**
     * Studio 2 is switched on.
     */
    const ON_S2 = 2;

    /**
     * Both studios are switched on.
     */
    const ON_BOTH = 3;

    /**
     * Construct the Selector Object.
     */
    public function __construct()
    {
    }

    /**
     * Returns the current selector status.
     *
     * The command 'Q' returns a 4-digit number. The first digit is the currently
     * selected studio. The second is where it was selected from, the third
     * provides information about whether the selector is locked, and the fourth
     * about which studios are switched on.
     *
     * @return array {'studio' => [1-8], 'selectedfrom' => [0-3], 'lock' => [0-2],
     *               'power' => [0-3]}
     */
    public static function setQuery()
    {
        $data = self::cmd('Q');

        $state = str_split($data);

        $sel_status = [
            'studio' => (int) $state[0],
            'lock' => (int) $state[1],
            'selectedfrom' => (int) $state[2],
            'power' => (int) $state[3],
        ];

        return $sel_status;
    }

    /**
     * Locks the Studio Selector. The remote selector panels in the studio will
     * no longer operate the selector. The buttons on the main panel will
     * continue to work.
     */
    public static function setLock()
    {
        self::cmd('L');
    }

    /**
     * Runs a command against URY's Physical Studio Selector. Be careful.
     *
     * @param string $cmd (Q)uery, (L)ock, (U)nlock, S[1-8]
     *
     * @return string Status for Query, or ACK/FLK for other commands.
     */
    private static function cmd($cmd)
    {
        $h = fsockopen('tcp://'.Config::$selector_telnet_host, Config::$selector_telnet_port, $errno, $errstr, 10);

        //Read through the welcome "studio selector:" message (16x2bytes)
        fgets($h, 32);

        //Run command
        fwrite($h, $cmd."\n");

        //Read response (4x2bytes)
        $response = fgets($h, 16);

        fclose($h);

        //Remove the END
        return trim($response);
    }

    public static function setStudio($studio)
    {
        if (($studio <= 0) || ($studio > 8)) {
            return ['myradio_errors' => 'Invalid Studio ID'];
        }
        $status = self::getStatusAtTime();

        if ($studio == $status['studio']) {
            throw new MyRadioException('Source '.$studio.' is already selected');
        }
        if ((($studio == 1) && (!$status['s1power']))
            || (($studio == 2) && (!$status['s2power']))
            || (($studio == 4) && (!$status['s4power']))
        ) {
            throw new MyRadioException('Source '.$studio.' is not powered');
        }
        if ($status['lock'] != 0) {
            throw new MyRadioException('Selector Locked');
        }

        $response = self::cmd('S'.$studio);

        if ($response === 'FLK') {
            throw new MyRadioException('Selector Locked');
        } elseif ($response === 'ACK') {
            return [
            'studio' => $studio,
            'lock' => 0,
            'selectedfrom' => 1,
            's1power' => self::getStudio1PowerAtTime($time),
            's2power' => self::getStudio2PowerAtTime($time),
            's4power' => (self::remoteStreams()['s1']) ? true : false,
            'lastmod' => time(),
            ];
        }
    }

    /**
     * Returns what studio was on air at the time given.
     *
     * @param int $time
     *
     * @return int
     */
    public static function getStudioAtTime($time = null)
    {
        if (!isset($time)) {
            $time = time();
        }

        $result = Database::getInstance()->fetchOne(
            'SELECT action FROM public.selector WHERE time <= $1
            AND action >= 4 AND action <= 11
            ORDER BY time DESC',
            [CoreUtils::getTimestamp($time)]
        );

        if (!$result) {
            return 0;
        }

        return $result['action'] - 3;
    }

    /**
     * Returns where the selector was set from at the time given.
     *
     * @param int $time
     *
     * @return int
     */
    public static function getSetbyAtTime($time = null)
    {
        if (!isset($time)) {
            $time = time();
        }

        $result = Database::getInstance()->fetchOne(
            'SELECT setby FROM public.selector WHERE time <= $1
            AND action >= 4 AND action <= 11
            ORDER BY time DESC',
            [CoreUtils::getTimestamp($time)]
        );

        if (empty($result)) {
            return 0;
        }

        return (int) $result['setby'];
    }

    /**
     * Returns the power state of studio1 at the time given.
     *
     * @param int $time
     *
     * @return bool
     */
    public static function getStudio1PowerAtTime($time = null)
    {
        if (!isset($time)) {
            $time = time();
        }

        $result = Database::getInstance()->fetchOne(
            'SELECT action FROM public.selector WHERE time <= $1
            AND action >= 13 AND action <= 14
            ORDER BY time DESC',
            [CoreUtils::getTimestamp($time)]
        );

        if (empty($result)) {
            return false;
        }

        return $result['action'] == 13;
    }

    /**
     * Returns the power state of studio2 at the time given.
     *
     * @param int $time
     *
     * @return bool
     */
    public static function getStudio2PowerAtTime($time = null)
    {
        if (!isset($time)) {
            $time = time();
        }

        $result = Database::getInstance()->fetchOne(
            'SELECT action FROM public.selector WHERE time <= $1
            AND action >= 15 AND action <= 16
            ORDER BY time DESC',
            [CoreUtils::getTimestamp($time)]
        );

        if (empty($result)) {
            return false;
        }

        return $result['action'] == 15;
    }

    /**
     * Returns the lock state at the time given.
     *
     * @param int $time
     *
     * @return int
     */
    public static function getLockAtTime($time = null)
    {
        if (!isset($time)) {
            $time = time();
        }

        $result = Database::getInstance()->fetchOne(
            'SELECT action FROM public.selector WHERE time <= $1
            AND action >= 1 AND action <= 3
            ORDER BY time DESC
            LIMIT 1',
            [CoreUtils::getTimestamp($time)]
        );

        if (empty($result)) {
            return false;
        }

        return ($result['action'] == 3) ? 0 : (int) $result['action'];
    }

    /**
     * Returns the time last modified before the time given.
     *
     * @param int $time
     *
     * @return int
     */
    public static function getLastModAtTime($time = null)
    {
        if (!isset($time)) {
            $time = time();
        }

        $result = Database::getInstance()->fetchOne(
            'SELECT time FROM public.selector WHERE time <= $1
            ORDER BY time DESC',
            [CoreUtils::getTimestamp($time)]
        );

        if (!$result) {
            return 1;
        }

        return $result['time'];
    }

    /**
     * Returns the selector status at the time given.
     *
     * @param int $time
     *
     * @return array
     */
    public static function getStatusAtTime($time = null)
    {
        if (!isset($time)) {
            $time = time();
        }

        $status = self::remoteStreams();

        return [
            'ready' => $status['ready'],
            'studio' => self::getStudioAtTime($time),
            'lock' => self::getLockAtTime($time),
            'selectedfrom' => self::getSetbyAtTime($time),
            's1power' => self::getStudio1PowerAtTime($time),
            's2power' => self::getStudio2PowerAtTime($time),
            's4power' => (isset($status['s1'])) ? $status['s1'] : false,
            'lastmod' => self::getLastModAtTime($time),
        ];
    }

    /**
     * SERIOUSLY, KNOW WHAT YOU ARE DOING WITH THIS METHOD.
     *
     * Calling this method will *terminate station output*, replacing it with
     * our pre-mixed audio for use in cases of national emergency such as
     * terrorist attacks or the death of someone in the royal family.
     *
     * Jukebox has this file requested multiple times, then our studio selector
     * is told to switch to Jukebox, then lock itself so only technical staff can
     * restore studio functionality.
     *
     * It also emails an array of various important people to inform them that
     * this has happened.
     */
    public static function setObit()
    {
        //Empty all existing request queues
        iTones_Utils::emptyQueues();

        //Request the obit file a few times (5h of content)
        for ($i = 0; $i < 5; ++$i) {
            iTones_Utils::requestFile(Config::$jukebox_obit_file);
        }

        //Skip to the next track
        iTones_Utils::skip();

        //Switch to studio 3
        try {
            self::setStudio(3);
        } catch (MyRadioException $e) {
            trigger_error('OBIT: Could not change selector source: '.$e->getMessage());
        }

        //Lock the selector
        self::lock();

        //Email people
        MyRadioEmail::sendEmailToComputing(
            'OBIT INITIATED',
            'Urgent: Initiated Obit procedure for station as requested by '
            .MyRadio_User::getInstance()->getName().' - '
            .MyRadio_User::getInstance()->getEmail()
        );

        //Store the event for Timelord
        file_put_contents('/tmp/myradio-obit', 1);
    }

    /**
     * Returns if an obit event is happening.
     */
    public static function isObitHappening()
    {
        if (file_exists('/tmp/myradio-obit')) {
            return (bool) file_get_contents('/tmp/myradio-obit');
        } else {
            return false;
        }
    }

    /**
     * Returns the state of the remote OB feeds in an associative array.
     *
     * @return array
     */
    public static function remoteStreams()
    {
        if (file_exists(Config::$ob_remote_status_file)) {
            $data = file(Config::$ob_remote_status_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

            if ($data) {
                $response = ['ready' => true];
                foreach ($data as $feed) {
                    $state = explode('=', $feed);
                    $response[trim($state[0])] = (bool) trim($state[1]);
                }

                return $response;
            }
        }

        return [
            'ready' => false,
        ];
    }

    /**
     * Returns the length of the current silence, if any.
     *
     * @return int
     */
    public static function isSilence()
    {
        $result = Database::getInstance()->fetchOne(
            'SELECT EXTRACT(epoch FROM starttime) AS starttime, EXTRACT(epoch FROM stoptime) AS stoptime
            FROM jukebox.silence_log
            ORDER BY silenceid DESC'
        );

        if (empty($result['stoptime'])) {
            return time() - $result['starttime'];
        } else {
            return 0;
        }
    }
}
