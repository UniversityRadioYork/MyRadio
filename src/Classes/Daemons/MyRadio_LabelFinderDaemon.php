<?php
/**
 * This file provides the MyRadio_LabelFinderDaemon class for MyRadio
 * @package MyRadio_Daemon
 */

/**
 * The LabelFinder Daemon processes rec_record in batches, and tries to fill in the "label" field.
 *
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130711
 * @package MyRadio_Daemon
 */
class MyRadio_LabelFinderDaemon extends MyRadio_Daemon
{
    /**
     * If this method returns true, the Daemon host should run this Daemon. If it returns false, it must not.
     * It is currently enabled because we have a lot of labels that needed filling in for Tracklisting.
     * @return boolean
     */
    public static function isEnabled()
    {
        return Config::$d_LabelFinder_enabled;
    }

    /**
     * THE DISCOGS API IS RATE LIMITED TO ONE REQUEST PER SECOND.
     */
    public static function run()
    {
        //Get 5 albums without labels
        $albums = Database::getInstance()->fetchAll(
            'SELECT recordid, title, artist FROM public.rec_record
            WHERE recordlabel=\'\' ORDER BY RANDOM() LIMIT 5'
        );

        foreach ($albums as $album) {
            dlog('Checking record '.$album['recordid'].' for label metadata', 4);
            $data = json_decode(
                file_get_contents(
                    'http://api.discogs.com/database/search?artist='.urlencode($album['artist'])
                    .'&release_title='.urlencode($album['title']).'&type=release'
                ),
                true
            );

            if (!empty($data['results'])) {
                $label = $data['results'][0]['label'][0];

                dlog("Setting {$album['recordid']} label to {$label}", 2);
                Database::getInstance()->query(
                    'UPDATE public.rec_record SET recordlabel=$1 WHERE recordid=$2',
                    [$label, $album['recordid']]
                );
            } else {
                dlog(
                    'No record label data improvement available for '
                    .$album['recordid'],
                    4
                );
            }
            sleep(1);
        }
    }
}
