<?php

/*
 * This file provides the SIS_Remote class for MyRadio
 * @package MyRadio_SIS
 */

namespace MyRadio\SIS;

use \MyRadio\Config;
use \MyRadio\ServiceAPI\ServiceAPI;
use \MyRadio\ServiceAPI\MyRadio_Selector;
use \MyRadio\ServiceAPI\MyRadio_Webcam;
use \MyRadio\MyRadio\MyRadioNews;

/**
 * This class has helper functions for long-polling SIS
 *
 * @package MyRadio_SIS
 */
class SIS_Remote extends ServiceAPI
{

    /**
     * Gets the latest presenter info
     * @param  array $session phpSession variable
     * @return array presenter info data
     */
    public static function queryPresenterInfo($session)
    {
        $time = 0;
        if (isset($_REQUEST['presenterinfo-lasttime'])) {
            $time = (int)$_REQUEST['presenterinfo-lasttime'];
        }
        if ($time < time() - 300) {
            $response = MyRadioNews::getLatestNewsItem(Config::$presenterinfo_feed);
            return [
                'presenterinfo' => ['time' => time(), 'info' => $response]
            ];
        } else {
            return [];
        }
    }

    /**
     * Gets the latest messages for the selected timeslot
     * @param  array $session phpSession variable
     * @return array message data
     */
    public static function queryMessages($session)
    {
        $response = SIS_Messages::getMessages($session['timeslotid'], isset($_REQUEST['messages_highest_id']) ? $_REQUEST['messages_highest_id'] : 0);

        if (!empty($response) && $response !== false) {
            return ['messages' => $response];
        }
    }

    /**
     * Gets the latest tracklist data for the selected timeslot
     * @param  array $session phpSession variable
     * @return array tracklist data
     */
    public static function queryTracklist($session)
    {
        $response = SIS_Tracklist::getTrackListing($session['timeslotid'], isset($_REQUEST['tracklist_highest_id']) ? $_REQUEST['tracklist_highest_id'] : 0);

        if (!empty($response) && $response !== false) {
            return ['tracklist' => $response];
        }

    }

    /**
     * Gets the latest selector status
     * @param  array $session phpSession variable
     * @return array selector status
     */
    public static function querySelector($session)
    {
        $time = 0;
        if (isset($_REQUEST['selector-lasttime'])) {
            $time = (int)$_REQUEST['selector-lasttime'];
        }

        $response = MyRadio_Selector::getStatusAtTime();

        if ($response['lastmod'] > $time) {
            return ['selector' => $response];
        }
    }

    /**
     * Gets the latest webcam status
     * @param  array $session phpSession variable
     * @return array webcam status
     */
    public static function queryWebcam($session)
    {
        $response = MyRadio_Webcam::getCurrentWebcam();
        $current = null;
        if (isset($_REQUEST['webcam-id'])) {
            $current = (int)$_REQUEST['webcam-id'];
        }

        if ($response['current'] !== $current) {
            return [
                'webcam' => [
                    'status' => $response,
                    'streams' => MyRadio_Webcam::getStreams()
                ]
            ];
        }
    }
}
