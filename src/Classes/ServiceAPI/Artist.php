<?php
/**
 * This file provides the Artist class for MyRadio
 * @package MyRadio_Core
 */

namespace MyRadio\ServiceAPI;

use \MyRadio\MyRadioException;
use \MyRadio\Config;

/**
 * The Artist class provides and stores information about a Artist
 *
 * @version 20130605
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @todo The completion of this module is impossible as Artists do not have
 * unique identifiers. For this to happen, BAPS needs to be replaced/updated
 * @package MyRadio_Core
 * @uses \Database
 */
class Artist extends ServiceAPI
{
    /**
     * Initiates the Artist object
     * @param int $artistid The ID of the Artist to initialise
     */
    protected function __construct($artistid)
    {
        $this->artistid = $artistid;
        throw new MyRadioException('Not implemented Artist::__construct');
    }

    /**
     * Returns an Array of Artists matching the given partial name
     * @param  String $title A partial or total title to search for
     * @param  int    $limit The maximum number of tracks to return
     * @return Array  2D with each first dimension an Array as follows:<br>
     *                      title: The name of the artist<br>
     *                      artistid: Always 0 until Artist support is implemented
     */
    public static function findByName($title, $limit)
    {
        $title = trim($title);

        return self::$db->fetchAll(
            'SELECT DISTINCT rec_track.artist AS title, 0 AS artistid
            FROM rec_track WHERE rec_track.artist ILIKE \'%\' || $1 || \'%\' LIMIT $2',
            [$title, $limit]
        );
    }

    /**
     *
     * @param Array $options One or more of the following:
     *                       title: String title of the track
     *                       artist: String artist name of the track
     *                       digitised: If true, only return digitised tracks. If false, return any.
     *                       limit: Maximum number of items to return. 0 = No Limit
     *                       trackid: int Track id
     *                       lastfmverified: Boolean whether or not verified with Last.fm Fingerprinter. Default any.
     *                       random: If true, sort randomly
     *                       idsort: If true, sort by trackid
     *                       custom: A custom SQL WHERE clause
     *                       precise: If true, will only return exact matches for artist/title
     *                       nocorrectionproposed: If true, will only return items with no correction proposed.
     *                       clean: Default any. 'y' for clean tracks, 'n' for dirty, 'u' for unknown.
     *
     */
    public static function findByOptions($options)
    {
        self::wakeup();

        if (empty($options['title'])) {
            $options['title'] = '';
        }
        if (empty($options['artist'])) {
            $options['artist'] = '';
        }
        if (empty($options['album'])) {
            $options['album'] = '';
        }
        if (!isset($options['digitised'])) {
            $options['digitised'] = true;
        }
        if (empty($options['itonesplaylistid'])) {
            $options['itonesplaylistid'] = null;
        }
        if (!isset($options['limit'])) {
            $options['limit'] = Config::$ajax_limit_default;
        }
        if (empty($options['trackid'])) {
            $options['trackid'] = null;
        }
        if (empty($options['lastfmverified'])) {
            $options['lastfmverified'] = null;
        }
        if (empty($options['random'])) {
            $options['random'] = null;
        }
        if (empty($options['idsort'])) {
            $options['idsort'] = null;
        }
        if (empty($options['custom'])) {
            $options['custom'] = null;
        }
        if (empty($options['precise'])) {
            $options['precise'] = false;
        }
        if (empty($options['nocorrectionproposed'])) {
            $options['nocorrectionproposed'] = false;
        }
        if (empty($options['clean'])) {
            $options['clean'] = false;
        }

        //Prepare paramaters
        $sql_params = [$options['title'], $options['artist'], $options['album'], $options['precise'] ? '' : '%'];
        $count = 4;
        if ($options['limit'] != 0) {
            $sql_params[] = $options['limit'];
            $count++;
            $limit_param = $count;
        }
        if ($options['clean']) {
            $sql_params[] = $options['clean'];
            $count++;
            $clean_param = $count;
        }

        //Do the bulk of the sorting with SQL
        $result = self::$db->fetchAll(
            'SELECT DISTINCT rec_track.artist
            FROM rec_track
            INNER JOIN rec_record ON ( rec_track.recordid = rec_record.recordid )
            WHERE rec_track.title ILIKE $4 || $1 || $4
            AND rec_track.artist ILIKE $4 || $2 || $4
            AND rec_record.title ILIKE $4 || $3 || $4'
            . ($options['digitised'] ? ' AND digitised=\'t\'' : '')
            . ($options['lastfmverified'] === true ? ' AND lastfm_verified=\'t\'' : '')
            . ($options['lastfmverified'] === false ? ' AND lastfm_verified=\'f\'' : '')
            . ($options['nocorrectionproposed'] === true ? ' AND trackid NOT IN (
                SELECT trackid FROM public.rec_trackcorrection WHERE state=\'p\'
                )' : '')
            . ($options['clean'] != null ? ' AND clean=$'.$clean_param : '')
            . ($options['custom'] !== null ? ' AND ' . $options['custom'] : '')
            . ($options['random'] ? ' ORDER BY RANDOM()' : '')
            . ($options['idsort'] ? ' ORDER BY trackid' : '')
            . ($options['limit'] == 0 ? '' : ' LIMIT $'.$limit_param),
            $sql_params
        );

        return $result;
    }
}
