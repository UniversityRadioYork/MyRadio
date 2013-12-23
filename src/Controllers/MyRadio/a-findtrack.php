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
    //Will use AND if multiple options set, otherwise OR and use 'term' for
    //both title and artist
    $options = [];
    $others = false;
    if (isset($_REQUEST['term'])) {
        $options['title'] = $_REQUEST['term'];
    }
    if (isset($_REQUEST['artist'])) {
        $options['artist'] = $_REQUEST['artist'];
        $others = true;
    }
    if (isset($_REQUEST['limit'])) {
        $options['limit'] = intval($_REQUEST['limit']);
        $others = true;
    }
    if (isset($_REQUEST['require_digitised'])) {
        $options['digitised'] = (bool)$_REQUEST['require_digitised'];
        $others = true;
    }
    if (isset($_REQUEST['itonesplaylistid'])) {
        $options['itonesplaylistid'] = $_REQUEST['itonesplaylistid'];
        $others = true;
    }
    
    if (!$others) {
        $options['artist'] = $_REQUEST['term'];
        $options['operator'] = 'OR';
    }
    
  $data = MyRadio_Track::findByOptions($options);
}
require 'Views/MyRadio/datatojson.php';