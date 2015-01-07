<?php

/**
 * This file provides the MyRadio_ExplicitDaemon class for MyRadio
 * @package MyRadio_Daemon
 */

namespace MyRadio\Daemons;

use \MyRadio\Config;
use \MyRadio\ServiceAPI\MyRadio_Track;

/**
 * The Explicit Daemon asks iTunes if a Track is Explicit.
 *
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130711
 * @package MyRadio_Daemon
 */
class MyRadio_ExplicitDaemon extends \MyRadio\MyRadio\MyRadio_Daemon
{
    private $digitised_only = true;
    /**
     * If this method returns true, the Daemon host should run this Daemon. If it returns false, it must not.
     * It is currently enabled because we have a lot of labels that needed filling in for Tracklisting.
     * @return boolean
     */
    public static function isEnabled()
    {
        return Config::$d_Explicit_enabled;
    }

    public static function run()
    {
        $tracks = MyRadio_Track::findByOptions(
            ['clean' => 'u',
            'limit' => 25,
            'random' => true,
            'digitised' => $this->digitised_only]
        );
        
        if (empty($tracks)) {
            $this->digitised_only = false;
        }

        foreach ($tracks as $track) {
            $q = str_replace(' ', '+', trim($track->getTitle() . ' ' . $track->getArtist()));
            $data = json_decode(
                file_get_contents(
                    'http://itunes.apple.com/search?term='
                    . $q . '&entity=song&limit=5'
                ),
                true
            );

            for ($i = 0; $i < $data['resultCount']; $i++) {
                /**
                 * explicit (explicit lyrics, possibly explicit album cover), cleaned
                 * (explicit lyrics "bleeped out"), notExplicit (no explicit lyrics)
                 */
                if ($data['results'][$i]['trackName'] == $track->getTitle()
                    && $data['results'][$i]['artistName'] == $track->getArtist()) {

                    $clean = $data['results'][$i]['trackExplicitness'] == 'explicit'
                            ? 'n' : 'y';
                    $track->setClean($clean);

                    dlog(
                        $track->getTitle() . ' (' . $track->getAlbum()->getID()
                        . '/' . $track->getID() . ') is ' . $clean,
                        2
                    );
                    break;
                }
            }
        }
    }
}
