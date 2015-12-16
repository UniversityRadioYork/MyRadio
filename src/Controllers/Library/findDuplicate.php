<?php

/**
 * Scan music library, finding tracks that seem to exist more than once.
 */
use \MyRadio\Database;
use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\ServiceAPI\MyRadio_Track;

//This stores the last ID checked so that we can collect Tracks in batches, preventing us from using ALL the RAM
$finalid = 0;
//Stores trackids of tracks that have been searched through already
$alreadydone = [];
//Stores MyRadio_Tracks that are duplicates
$duplicates = [];
//We use this here to keep track of the query counter
$db = Database::getInstance();
//If query count goes over this, bail and show what you've found so far
$query_limit = 25000;

do {
    //Get the next batch of tracks from where we left off
    $tracks = MyRadio_Track::findByOptions(
        [
            'limit' => 500,
            'digitised' => true,
            'idsort' => true,
            'custom' => 'trackid > '.$finalid,
        ]
    );

    foreach ($tracks as $track) {
        //If this has already appeared as a duplicate, don't search again or we'll duplicate the duplications
        if (in_array($track->getID(), $alreadydone)) {
            continue;
        }

        //Find tracks that match this name and artist
        $matches = MyRadio_Track::findByOptions(
            [
                'title' => $track->getTitle(),
                'artist' => $track->getArtist(),
                'limit' => 0,
                'precise' => true,
            ]
        );

        //If there's more than one match, then there are duplicates for this item
        if (sizeof($matches) > 1) {
            foreach ($matches as $match) {
                $alreadydone[] = $match->getID();
                $duplicates[] = $match;
            }
        }

        //Log the latest ID used
        $finalid = $track->getID();

        //Remove the Singleton store's built in reference to this track to reduce memory usage
        $track->removeInstance();
        unset($track);

        //Kill the loop if we've used too many queries
        if ($db->getCounter() > $query_limit) {
            break;
        }
    }
    echo "$finalid ({$db->getCounter()}/".sizeof($duplicates).')<br>';
    gc_collect_cycles();

    if ($db->getCounter() > $query_limit) {
        break;
    }
} while (!empty($tracks));

CoreUtils::getTemplateObject()->setTemplate('table.twig')
    ->addVariable('tablescript', 'myury.datatable.default')
    ->addVariable('title', 'Duplicate Tracks')
    ->addVariable('tabledata', CoreUtils::dataSourceParser($duplicates))
    ->render();
