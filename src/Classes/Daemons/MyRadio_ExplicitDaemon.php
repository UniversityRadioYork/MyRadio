<?php

/**
 * This file provides the MyRadio_ExplicitDaemon class for MyRadio
 * @package MyRadio_Daemon
 */

namespace MyRadio\Daemons;

use \MyRadio\Config;
use \MyRadio\Database;
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
    private static $digitised_only = true;

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
        $db = Database::getInstance();

        $tracks = MyRadio_Track::findByOptions(
            [
                'clean' => 'u',
                'limit' => 25,
                'random' => true,
                'digitised' => self::$digitised_only,
                'custom' => 'trackid NOT IN (SELECT trackid FROM music.explicit_checked)'
            ]
        );
        
        if (empty($tracks)) {
            self::$digitised_only = false;
        }

        foreach ($tracks as $track) {
            $q = trim($track->getTitle() . ' ' . $track->getArtist());
            $data = json_decode(
                file_get_contents(
                    'http://itunes.apple.com/search?term='
                    . urlencode($q) . '&entity=song&limit=5'
                ),
                true
            );

            dlog('Checking ' . $q . ' (' . $data['resultCount'] . ' matches)', 4);

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
                    
                    dlog('Setting Explicicity of ' . 
                        $track->getTitle() . ' (' . $track->getAlbum()->getID()
                        . '/' . $track->getID() . ') as ' . $clean,
                        2
                    );
                    break;
                }
            }

            $db->query('INSERT INTO music.explicit_checked VALUES ($1)', [$track->getID()]);
        }
    }
}
