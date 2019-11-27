<?php

use MyRadio\Database;
use MyRadio\MyRadio\CoreUtils;
use MyRadio\ServiceAPI\MyRadio_Show;

require_once '../src/Controllers/root_cli.php';

echo 'Processing all shows...';

$shows = MyRadio_Show::getAllShows();

$numShows = count($shows);
$batchSize = 100;
$startOfBatch = 0;

while ($startOfBatch < $numShows) {
    echo "Processing the $batchSize shows after $startOfBatch...";

    Database::getInstance()->query('BEGIN');
    for ($i = $startOfBatch; $i < $startOfBatch + $batchSize; $i++) {
        $show = $shows[$i];
        $subtype = CoreUtils::getSubtypeForShow($show->getMeta('title'));
        Database::getInstance()->query('INSERT INTO schedule.show_season_subtype
            (show_id, show_subtype_id, effective_from)
            (SELECT $1, (SELECT show_subtype_id
                FROM schedule.show_subtypes WHERE schedule.show_subtypes.class = $2
            ), NOW())', [$show->getID(), $subtype]);
    }
    Database::getInstance()->query('COMMIT');
    echo "Processed.";
}

echo "Done.";

