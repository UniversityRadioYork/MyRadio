<?php

/**
 * Allows querying the Central Track Database, returning a JSON result
 * 
 * Parameters:
 * 'term': Full or Partial Title of track. May be blank.
 * 'artist': Full or Partial Artist of track
 * 'limit': Maximum number of results, default Config::$ajax_limit_default. 0 Means return ALL results.
 * 'digitised': Boolean If true, only digitised tracks are returned
 * 'itonesplaylistid': The ID if an itones playlist the tracks must be in
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130626
 * @package MyRadio_Core
 */
if (isset($_REQUEST['id'])) {
    $data = MyRadio_Track::getInstance((int) $_REQUEST['id']);
} else {

    $data = MyRadio_Track::findByOptions(array(
                'title' => isset($_REQUEST['term']) ? $_REQUEST['term'] : '',
                'artist' => isset($_REQUEST['artist']) ? $_REQUEST['artist'] : '',
                'limit' => isset($_REQUEST['limit']) ? intval($_REQUEST['limit']) : Config::$ajax_limit_default,
                'digitised' => isset($_REQUEST['require_digitised']) ? (bool) $_REQUEST['require_digitised'] : false,
                'itonesplaylistid' => isset($_REQUEST['itonesplaylistid']) ? $_REQUEST['itonesplaylistid'] : ''
    ));
}
require 'Views/MyRadio/datatojson.php';