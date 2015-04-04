<?php

/**
 * Allows querying the Central Track Database, returning a JSON result
 *
 * Parameters:
 * 'term': Full or Partial Title of track. May be blank.
 * 'artist': Full or Partial Artist of track
 * 'limit': Maximum number of results, default $container['config']->ajax_limit_default. 0 Means return ALL results.
 * 'digitised': Boolean If true, only digitised tracks are returned
 * 'itonesplaylistid': The ID if an itones playlist the tracks must be in
 *
 * @package MyRadio_Core
 */

use \MyRadio\Config;
use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\ServiceAPI\MyRadio_Track;

if (isset($_REQUEST['id'])) {
    $data = MyRadio_Track::getInstance((int) $_REQUEST['id']);
} else {

    $data = MyRadio_Track::findByOptions(
        [
                'title' => isset($_REQUEST['term']) ? $_REQUEST['term'] : '',
                'artist' => isset($_REQUEST['artist']) ? $_REQUEST['artist'] : '',
                'limit' => isset($_REQUEST['limit']) ? intval($_REQUEST['limit']) : $container['config']->ajax_limit_default,
                'digitised' => isset($_REQUEST['require_digitised']) ? (bool) $_REQUEST['require_digitised'] : false,
                'itonesplaylistid' => isset($_REQUEST['itonesplaylistid']) ? $_REQUEST['itonesplaylistid'] : ''
        ]
    );
}
echo CoreUtils::dataToJSON($data);
