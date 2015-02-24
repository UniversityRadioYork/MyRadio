<?php

/**
 * This file provides the MyRadio_PodcastDeamon class for MyRadio
 * @package MyRadio_Daemon
 */

namespace MyRadio\Daemons;

use \MyRadio\Config;
use \MyRadio\ServiceAPI\MyRadio_Podcast;

/**
 * The Podcast Daemon converts uploaded audio files into web-ready uryplayer files.
 *
 * @package MyRadio_Daemon
 */
class MyRadio_PodcastDaemon extends \MyRadio\MyRadio\MyRadio_Daemon
{
    /**
     * If this method returns true, the Daemon host should run this Daemon. If it returns false, it must not.
     * @return boolean
     */
    public static function isEnabled()
    {
        return Config::$d_Podcast_enabled;
    }

    public static function run()
    {
        dlog('Checking for pending Podcasts...', 4);
        $podcasts = MyRadio_Podcast::getPending();

        if (!empty($podcasts)) {
            //Encode the first podcast.
            dlog('Converting Podcast '.$podcasts[0]->getID().'...', 3);
            $podcasts[0]->convert();
            dlog('Converstion complete.', 3);
        }
    }
}
